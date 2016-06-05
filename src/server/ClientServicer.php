<?php
namespace nntp\server;

use Generator;
use Icicle\Stream\DuplexStream;
use Icicle\Stream\Exception\UnreadableException;
use nntp\Group;
use nntp\protocol\{Command, Encoder, Response};


/**
 * Services requsts for a connected client.
 *
 * A servicer instance manages the server-side state for the remote client and interprets client commands.
 */
class ClientServicer
{
    private $stream;
    private $encoder;
    private $accessLayer;
    private $running = false;
    private $currentGroup = null;
    private $currentArticle = null;

    public function __construct(DuplexStream $stream, Encoder $encoder, AccessLayer $accessLayer)
    {
        $this->stream = $stream;
        $this->encoder = $encoder;
        $this->accessLayer = $accessLayer;
    }

    /**
     * Starts the servicer.
     */
    public function start(): Generator
    {
        // Send a welcome message.
        $welcome = new Response(200, 'Ready');
        yield from $this->encoder->writeResponse($this->stream, $welcome);

        // Command loop
        $this->running = true;
        while ($this->running) {
            // Parse incoming commands.
            try {
                $command = yield from $this->encoder->readCommand($this->stream);
            } catch (UnreadableException $e) {
                // Client disconnected
                break;
            }

            var_dump($command);

            // Determine the command name and choose how to handle it.
            try {
                switch ($command->name()) {
                    // Mandatory commands
                    case 'CAPABILITIES':
                        yield from $this->handleCapabilities($command);
                        break;
                    case 'HEAD':
                        yield from $this->handleHead($command);
                        break;
                    case 'HELP':
                        yield from $this->handleHelp($command);
                        break;
                    case 'QUIT':
                        yield from $this->handleQuit($command);
                        break;
                    case 'STAT':
                        yield from $this->handleStat($command);
                        break;

                    // LIST commands
                    case 'LIST':
                        yield from $this->handleList($command);
                        break;

                    // NEWNEWS commands
                    case 'NEWNEWS':
                        yield from $this->handleNewNews($command);
                        break;

                    // POST commands
                    case 'POST':
                        yield from $this->handlePost($command);
                        break;

                    // READER commands
                    case 'ARTICLE':
                        yield from $this->handleArticle($command);
                        break;
                    case 'BODY':
                        yield from $this->handleBody($command);
                        break;
                    case 'DATE':
                        yield from $this->handleDate($command);
                        break;
                    case 'GROUP':
                        yield from $this->handleGroup($command);
                        break;
                    case 'LAST':
                        yield from $this->handleLast($command);
                        break;
                    case 'LISTGROUP':
                        yield from $this->handleListGroup($command);
                        break;
                    case 'NEWGROUPS':
                        yield from $this->handleNewGroups($command);
                        break;
                    case 'NEXT':
                        yield from $this->handleNext($command);
                        break;

                    // Unknown command
                    default:
                        yield from $this->sendResponse(new Response(500, 'Unknown command'));
                        break;
                }
            } catch (\Throwable $e) {
                yield from $this->sendResponse(new Response(502, 'Unhandled exception: ' . $e->getMessage()));
            }
        }

        $this->stream->close();
    }

    public function stop()
    {
        $this->running = false;
    }

    public function handleCapabilities(Command $command): Generator
    {
        yield from $this->sendResponse(new Response(101, 'Capability list follows (multi-line)'));

        // Generate the list of capabilities that we support.
        $data = implode("\r\n", [
            'VERSION 2',
            'READER',
            'POST',
            'NEWNEWS',
            'LIST ACTIVE',
            'IMPLEMENTATION coderstephen/nntp server',
        ]);

        yield from $this->sendData($data);
    }

    public function handleHelp(Command $command): Generator
    {
        yield from $this->sendResponse(new Response(100, 'Help text follows (multi-line)'));

        $data = implode("\r\n", [
            'article [message-id|number]',
            'body [message-id|number]',
            'capabilities',
            'date',
            'group group',
            'head [message-id|number]',
            'help',
            'last',
            'list active',
            'listgroup',
            'newgroups',
            'newnews',
            'next',
            'post',
            'quit',
            'stat',
        ]);

        yield from $this->sendData($data);
    }

    public function handleDate(Command $command): Generator
    {
        yield from $this->sendResponse(new Response(111, date('YmdHis')));
    }

    public function handleList(Command $command): Generator
    {
        yield from $this->sendResponse(new Response(215, 'List of newsgroups follows'));

        $groups = yield from $this->accessLayer->getGroups();

        $data = array_reduce($groups, function(string $s, Group $group) {
            switch ($group->status()) {
                case Group::POSTING_PERMITTED:
                    $status = 'y';
                    break;
                case Group::POSTING_NOT_PERMITTED:
                    $status = 'n';
                    break;
                case Group::POSTING_FORWARDED:
                    $status = 'm';
                    break;
                default:
                    $status = 'u';
                    break;
            }

            return $s . sprintf("%s %d %d %s\r\n",
                $group->name(),
                $group->highWaterMark(),
                $group->lowWaterMark(),
                $status);
        }, '');

        yield from $this->sendData($data);
    }

    public function handleGroup(Command $command): Generator
    {
        if ($command->argCount() !== 1) {
            yield from $this->sendResponse(new Response(501, 'Invalid number of arguments'));
            return;
        }

        $name = $command->arg(0);
        $group = yield from $this->accessLayer->getGroupByName($name);

        if (!$group) {
            yield from $this->sendResponse(new Response(411, 'No such newsgroup'));
            return;
        }

        $this->currentGroup = $group;
        $this->currentArticle = $group->lowWaterMark();
        yield from $this->sendResponse(new Response(211, $group->count() . ' ' . $group->lowWaterMark() . ' ' . $group->highWaterMark() . ' ' . $group->name() . ' Group successfully selected'));
    }

    public function handleQuit(Command $command): Generator
    {
        yield from $this->sendResponse(new Response(205, 'Closing connection'));
        $this->stop();
    }

    public function handleArticle(Command $command): Generator
    {
        $article = yield from $this->fetchArticle($command->args());

        if ($article) {
            yield from $this->sendResponse(new Response(220, '%d %s Article follows (multi-line)', $article->number(), $article->id()));
            yield from $this->sendData((string)$article);
        }
    }

    public function handleHead(Command $command): Generator
    {
        $article = yield from $this->fetchArticle($command->args());

        if ($article) {
            yield from $this->sendResponse(new Response(221, '%d %s Headers follow (multi-line)', $article->number(), $article->id()));
            yield from $this->sendData((string)$article->headers());
        }
    }

    public function handleBody(Command $command): Generator
    {
        $article = yield from $this->fetchArticle($command->args());

        if ($article) {
            yield from $this->sendResponse(new Response(222, '%d %s Body follows (multi-line)', $article->number(), $article->id()));
            yield from $this->sendData($article->body());
        }
    }

    protected function fetchArticle(string ...$args): Generator
    {
        // Current article?
        if (count($args) === 0) {
            if ($this->currentGroup === null) {
                yield from $this->sendResponse(new Response(412, 'No newsgroup selected'));
                return;
            }

            $article = $this->accessLayer->getArticleByNumber($this->group, $this->currentArticle);
            if (!$article) {
                yield from $this->sendResponse(new Response(420, 'Current article number is invalid'));
                return;
            }
        }

        // Article number?
        elseif (is_numeric($args[0])) {
            $number = (int)$args[0];

            if ($this->currentGroup === null) {
                yield from $this->sendResponse(new Response(412, 'No newsgroup selected'));
                return;
            }

            $article = $this->accessLayer->getArticleByNumber($this->group, $number);
            if (!$article) {
                yield from $this->sendResponse(new Response(423, 'No article with that number'));
                return;
            }
        }

        // Article ID
        else {
            $article = $this->accessLayer->getArticleById($args[0]);
            if (!$article) {
                yield from $this->sendResponse(new Response(430, 'No article with that message-id'));
                return;
            }
        }

        return $article;
    }

    protected function sendResponse(Response $response): Generator
    {
        return $this->encoder->writeResponse($this->stream, $response);
    }

    protected function sendData(string $data): Generator
    {
        return $this->encoder->writeData($this->stream, $data);
    }
}
