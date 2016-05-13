<?php
namespace LibNNTP;

use Icicle\Dns;
use LibNNTP\Protocol\Command;


class Client
{
    private $socket;
    private $reader;
    private $writer;


    public function __construct()
    {
        $this->reader = new Protocol\ResponseReader();
        $this->writer = new Protocol\CommandWriter();
    }

    public function connect(string $host, int $port = 119, array $options = []): \Generator
    {
        // Connect to te remote host.
        $this->socket = yield from Dns\connect($host, $port, $options);

        var_dump(yield from $this->reader->readResponse($this->socket));

        // Get the server capabilities.
        $this->getServerCapabilities();
    }

    public function sendCommand(Command $command): \Generator
    {
        yield from $this->writer->writeCommand($this->socket, $command);
        $response = yield from $this->reader->readResponse($this->socket, $command->isMultiLine());
        return $response;
    }

    public function getGroups(): \Generator
    {
        $command = new Command('LIST ACTIVE', [], true);
        $response = yield from $this->sendCommand($command);

        if (!$response->isOk()) {
            throw new \Exception();
        }

        if (preg_match_all('/([A-z\._-]+)\s+(\d+)\s+(\d+)\s+(\w)/', $response->data(), $matches, PREG_SET_ORDER) === false) {
            throw new \Exception();
        }

        return array_map(function ($match) {
            if ($match[4] === 'y') {
                $status = Group::POSTING_PERMITTED;
            } elseif ($match[4] === 'n') {
                $status = Group::POSTING_NOT_PERMITTED;
            } elseif ($match[4] === 'm') {
                $status = Group::POSTING_FORWARDED;
            } else {
                $status = Group::STATUS_UNKNOWN;
            }

            return new Group($match[1], (int)$match[2], (int)$match[3], $status);
        }, $matches);
    }

    public function chooseGroup(string $group): \Generator
    {
        return yield from $this->sendCommand(new Command('GROUP', [
            $group
        ]));
    }

    public function readerMode(): \Generator
    {
        return yield from $this->sendCommand(new Command('MODE READER'));
    }

    public function getArticles()
    {}

    public function close(): \Generator
    {
        yield from $this->sendCommand(new Command('QUIT'));
        $this->socket->close();
    }

    protected function getServerCapabilities(): \Generator
    {
        // Get the server capabilities.
        $command = new Command('CAPABILITIES', [], true);
        $response = yield from $this->sendCommand($command);

        if ($response->code() !== 101) {
            return [];
        }
    }
}
