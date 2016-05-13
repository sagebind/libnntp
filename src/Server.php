<?php
namespace LibNNTP;

use Icicle\Coroutine\Coroutine;
use Icicle\Socket\Server\{DefaultServerFactory, ServerFactory, Server as SocketServer};
use Icicle\Socket\Socket;
use LibNNTP\Protocol\Command;
use LibNNTP\Protocol\Response;


class Server
{
    const DEFAULT_ADDRESS = '0.0.0.0';

    private $factory;
    private $servers;
    private $reader;
    private $writer;

    public function __construct(ServerFactory $factory = null)
    {
        $this->factory = $factory ?: new DefaultServerFactory();
        $this->servers = [];
        $this->reader = new Protocol\CommandReader();
        $this->writer = new Protocol\ResponseWriter();
    }

    public function listen(int $port = 119, string $address = self::DEFAULT_ADDRESS, array $options = [])
    {
        // Create a server for the requested address and port.
        $server = $this->factory->create($address, $port, $options);
        $this->servers[] = $server;

        $coroutine = new Coroutine($this->accept($server));
        $coroutine->done();
    }

    public function close()
    {
        foreach ($this->servers as $server) {
            $server->close();
        }
    }

    private function accept(SocketServer $server): \Generator
    {
        while ($server->isOpen()) {
            // Wait for a client to connect.
            $socket = yield from $server->accept();

            // Handle the client in a new coroutine.
            $coroutine = new Coroutine($this->handleClient($socket));
            $coroutine->done();
        }
    }

    private function handleClient(Socket $socket): \Generator
    {
        // Send a welcome message.
        $welcome = new Response(200, 'ready');
        yield from $this->writer->writeResponse($socket, $welcome);

        // Command loop
        while (true) {
            // Parse incoming commands.
            $command = yield from $this->reader->readCommand($socket);

            if ($command->name() === 'QUIT') {
                $response = new Response(205, 'closing connection');
                yield from $this->writer->writeResponse($socket, $response);
                $socket->close();
                break;
            }
        }
    }
}
