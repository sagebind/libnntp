<?php
namespace nntp\server;

use Generator;
use Icicle\Coroutine\Coroutine;
use Icicle\Socket\Server\{DefaultServerFactory, ServerFactory, Server as SocketServer};
use Icicle\Socket\Socket;
use nntp\protocol\{Command, Encoder, Response, Rfc3977Encoder};


/**
 * A full featured, integratable NNTP server.
 */
class Server
{
    const DEFAULT_ADDRESS = '0.0.0.0';

    private $accessLayer;
    private $factory;
    private $encoder;
    private $servers;

    public function __construct(AccessLayer $accessLayer = null, Encoder $encoder = null, ServerFactory $factory = null)
    {
        $this->accessLayer = $accessLayer;
        $this->factory = $factory ?: new DefaultServerFactory();
        $this->encoder = $encoder ?: new Rfc3977Encoder();
        $this->servers = [];
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

    private function accept(SocketServer $server): Generator
    {
        while ($server->isOpen()) {
            // Wait for a client to connect.
            $socket = yield from $server->accept();
            $this->handleClient($socket);
        }
    }

    private function handleClient(Socket $socket)
    {
        // Create a new client servicer for this client.
        $servicer = new ClientServicer($socket, $this->encoder, $this->accessLayer);

        // Run the servicer in a new coroutine.
        $coroutine = new Coroutine($servicer->start());
        $coroutine->done();
    }
}
