<?php
namespace nntp;

use Generator;
use DateTime;
use DateTimeZone;
use Icicle\Dns;
use Icicle\Stream\DuplexStream;
use nntp\protocol\{Command, Encoder, Rfc3977Encoder};


/**
 * A fully-featured NNTP client.
 *
 * Compatible with RFC 3977 and RFC 977.
 */
class Client
{
    private $encoder;
    private $capabilities = [];
    private $postingAllowed = true;
    private $group;

    /**
     * Connects to an NNTP server.
     */
    public static function connect(string $host, int $port = 119, array $options = []): Generator
    {
        // Connect to the remote host.
        $socket = yield from Dns\connect($host, $port, $options);

        // Encoder for encoding and decoding the protocol.
        $encoder = new Rfc3977Encoder();

        // Create the client object.
        $client = new static($socket, $encoder);

        // The server should respond with a welcome message.
        $response = yield from $encoder->readResponse($socket);

        // A 201 response code means posting is not allowed.
        if ($response->code() === 201) {
            $client->postingAllowed = false;
        }

        // Get the server capabilities.
        yield from $client->getServerCapabilities();

        // Set reader client mode if necessary.
        if ($client->hasCapability('MODE-READER') || !$client->hasCapability('READER')) {
            yield from $client->sendCommand(new Command('MODE READER'));
        }

        // Enable encryption if supported.
        if ($client->hasCapability('STARTTLS')) {
            yield from $client->sendCommand(new Command('STARTTLS'));
            yield from $socket->enableCrypto(STREAM_CRYPTO_METHOD_ANY_CLIENT);
        }

        return $client;
    }

    /**
     * Creates a new client with a given protocol encoder.
     */
    public function __construct(DuplexStream $stream, Encoder $encoder)
    {
        $this->stream = $stream;
        $this->encoder = $encoder;
    }

    /**
     * Checks if the server has a particular capability, according to RFC 3977.
     */
    public function hasCapability(string $capability): bool
    {
        return isset($this->capabilities[$capability]);
    }

    /**
     * Authenticates as a user.
     */
    public function authenticate(string $user, string $password = null): Generator
    {
        $response = yield from $this->sendCommand(new Command('AUTHINFO USER', $user));

        // Check if password is required.
        if ($response->code() === 381) {
            if ($password === null) {
                throw new \Exception("Password required");
            }

            yield from $this->sendCommand(new Command('AUTHINFO PASS', $password));
        }
    }

    /**
     * Gets the current UTC date and time on the server.
     */
    public function getDate(): Generator
    {
        $response = yield from $this->sendCommand(new Command('DATE'));
        return DateTime::createFromFormat('YmdHis', $response->message(), new DateTimeZone('UTC'));
    }

    /**
     * Gets a list of all groups in the server.
     */
    public function getGroups(): Generator
    {
        $command = new Command('LIST ACTIVE');
        $response = yield from $this->sendCommand($command);

        $data = yield from $this->encoder->readData($this->stream);

        if (preg_match_all('/([A-z\._-]+)\s+(\d+)\s+(\d+)\s+(\w)/', $data, $matches, PREG_SET_ORDER) === false) {
            throw new FormatException('Invalid groups format');
        }

        return array_map(function ($matches) {
            if ($matches[4] === 'y') {
                $status = Group::POSTING_PERMITTED;
            } elseif ($matches[4] === 'n') {
                $status = Group::POSTING_NOT_PERMITTED;
            } elseif ($matches[4] === 'm') {
                $status = Group::POSTING_FORWARDED;
            } else {
                $status = Group::STATUS_UNKNOWN;
            }

            $high = (int)$matches[2];
            $low = (int)$matches[3];
            return new Group($matches[1], $high - $low, $low, $high, $status);
        }, $matches);
    }

    /**
     * Gets an array of groups created after a given time.
     */
    public function getGroupsSince(DateTime $time): Generator
    {
        $command = new Command('NEWGROUPS', $time->format('Ymd His'), 'GMT');
        yield from $this->sendCommand($command);

        $data = yield from $this->encoder->readData($this->stream);
        return explode("\r\n", $data);
    }

    /**
     * Gets the currently selected newsgroup.
     */
    public function getCurrentGroup(): Group
    {
        return $this->group;
    }

    /**
     * Sets the currently selected newsgroup.
     */
    public function setCurrentGroup(string $group): Generator
    {
        $response = yield from $this->sendCommand(new Command('GROUP', $group));

        if (preg_match('/(\d+)\s+(\d+)\s+(\d+)\s+([A-z\._-]+)/', $response->message(), $matches) === false) {
            throw new FormatException('Invalid group format');
        }

        $this->group = new Group($matches[4], (int)$matches[1], (int)$matches[2], (int)$matches[3]);
        return $this->group;
    }

    /**
     * Checks if an article exists.
     */
    public function articleExists(string $id)
    {
        yield from $this->encoder->writeCommand($this->stream, new Command('STAT', $id));
        $response = yield from $this->encoder->readResponse($this->stream);
        return $response->code() === 223;
    }

    /**
     * Returns an array of article IDs that have been posted or received by the server after a given time.
     */
    public function getArticlesSince(string $group, DateTime $time): Generator
    {
        $command = new Command('NEWNEWS', $group, $time->format('Ymd His'), 'GMT');
        yield from $this->sendCommand($command);

        $data = yield from $this->encoder->readData($this->stream);
        return explode("\r\n", $data);
    }

    /**
     * Advances to the next article.
     */
    public function next(): Generator
    {
        yield from $this->sendCommand(new Command('NEXT'));
    }

    /**
     * Moves to the previous article.
     */
    public function previous(): Generator
    {
        yield from $this->sendCommand(new Command('LAST'));
    }

    /**
     * Gets the currently selected article.
     */
    public function getArticle(): Generator
    {
        yield from $this->sendCommand(new Command('ARTICLE'));

        $data = yield from $this->encoder->readData($this->stream);
        return Article::parse($data);
    }

    /**
     * Gets an article by ID.
     */
    public function getArticleById(string $id): Generator
    {
        yield from $this->sendCommand(new Command('ARTICLE', $id));

        $data = yield from $this->encoder->readData($this->stream);
        return Article::parse($data);
    }

    /**
     * Gets an article by its number in the current newsgroup.
     */
    public function getArticleByNumber(int $number): Generator
    {
        yield from $this->sendCommand(new Command('ARTICLE', (string)$number));

        $data = yield from $this->encoder->readData($this->stream);
        return Article::parse($data);
    }

    /**
     * Gets the headers of the currently selected article.
     */
    public function getArticleHeaders(): Generator
    {
        yield from $this->sendCommand(new Command('HEAD'));

        $data = yield from $this->encoder->readData($this->stream);
        return HeaderBag::parse($data);
    }

    /**
     * Gets the headers of an article by ID.
     */
    public function getArticleHeadersById(string $id): Generator
    {
        yield from $this->sendCommand(new Command('HEAD', $id));

        $data = yield from $this->encoder->readData($this->stream);
        return HeaderBag::parse($data);
    }

    /**
     * Gets the headers of an article by its number in the current newsgroup.
     */
    public function getArticleHeadersByNumber(int $number): Generator
    {
        yield from $this->sendCommand(new Command('HEAD', (string)$number));

        $data = yield from $this->encoder->readData($this->stream);
        return HeaderBag::parse($data);
    }

    /**
     * Gets the body text of the currently selected article.
     */
    public function getArticleBody(): Generator
    {
        yield from $this->sendCommand(new Command('BODY'));
        return yield from $this->encoder->readData($this->stream);
    }

    /**
     * Gets the body text of an article by ID.
     */
    public function getArticleBodyById(string $id): Generator
    {
        yield from $this->sendCommand(new Command('BODY', $id));
        return yield from $this->encoder->readData($this->stream);
    }

    /**
     * Gets the body text of an article by its number in the current newsgroup.
     */
    public function getArticleBodyByNumber(int $number): Generator
    {
        yield from $this->sendCommand(new Command('BODY', (string)$number));
        return yield from $this->encoder->readData($this->stream);
    }

    /**
     * Posts an article to the news server.
     */
    public function postArticle(Article $article): Generator
    {
        if (!$this->postingAllowed) {
            throw new \Exception("Posting not allowed");
        }

        // Tell the server we are about to post the article.
        yield from $this->sendCommand(new Command('POST'));

        // Send the encoded article.
        yield from $this->encoder->sendData($this->stream, (string)$article);

        // Verify the article posted successfully.
        $response = $this->encoder->readResponse($this->stream);
        if (!$response->isOk()) {
            throw new RemoteException($response->message(), $response->code());
        }
    }

    /**
     * Closes the connection.
     */
    public function close(): Generator
    {
        yield from $this->sendCommand(new Command('QUIT'));
    }

    /**
     * Sends an arbitrary command to the server and returns the response.
     */
    public function sendCommand(Command $command): Generator
    {
        yield from $this->encoder->writeCommand($this->stream, $command);
        $response = yield from $this->encoder->readResponse($this->stream);

        // If the command was unsuccessful, throw an exception with the server's error reason.
        if (!$response->isOk()) {
            throw new RemoteException($response->message(), $response->code());
        }

        return $response;
    }

    protected function getServerCapabilities(): Generator
    {
        try {
            $response = yield from $this->sendCommand(new Command('CAPABILITIES'));
            $data = yield from $this->encoder->readData($this->stream);
            $this->capabilities = array_flip(explode("\r\n", $data));
        } catch (RemoteException $e) {
            $this->capabilities = [];
        }
    }
}
