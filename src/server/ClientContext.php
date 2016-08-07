<?php
namespace nntp\server;

use Generator;
use Icicle\Log\{Log, function log};
use Icicle\Socket\Socket;
use nntp\protocol\Command;
use nntp\protocol\Encoder;
use nntp\protocol\Response;

/**
 * Stores the connection and state for a single connected client.
 */
class ClientContext
{
    private $socket;
    private $encoder;
    private $accessLayer;
    private $cursor;

    public function __construct(Socket $socket, Encoder $encoder, AccessLayer $accessLayer)
    {
        $this->socket = $socket;
        $this->encoder = $encoder;
        $this->accessLayer = $accessLayer;
        $this->cursor = new InvalidCursor();
    }

    public function getCursor(): GroupCursor
    {
        return $this->cursor;
    }

    public function setCursor(GroupCursor $cursor)
    {
        $this->cursor = $cursor;
    }

    public function getAccessLayer(): AccessLayer
    {
        return $this->accessLayer;
    }

    public function getSocket(): Socket
    {
        return $this->socket;
    }

    public function readCommand(): Generator
    {
        $command = yield from $this->encoder->readCommand($this->socket);

        yield from log()->log(Log::DEBUG, 'Received command: %s', $command);

        return $command;
    }

    public function writeResponse(Response $response): Generator
    {
        yield from log()->log(Log::DEBUG, 'Sent response: %s', $response);

        return yield from $this->encoder->writeResponse($this->socket, $response);
    }

    public function readData(): Generator
    {
        return yield from $this->encoder->readData($this->socket);
    }

    public function writeData(string $data): Generator
    {
        yield from log()->log(Log::DEBUG, "Sent data:\n%s", $data);

        return yield from $this->encoder->writeData($this->socket, $data);
    }
}
