<?php
namespace nntp\server;

use Generator;
use Icicle\Coroutine\Coroutine;
use Icicle\Log\{Log, function log};
use Icicle\Socket\Server\{DefaultServerFactory, ServerFactory, Server as SocketServer};
use Icicle\Socket\Socket;
use Icicle\Stream\Exception\{UnreadableException, UnwritableException};
use Icicle\Socket\Exception\ClosedException;
use nntp\Group;
use nntp\protocol\{Command, Encoder, Response, Rfc3977Encoder};
use nntp\server\handlers\Handler;


/**
 * A full featured, integratable NNTP server.
 */
class Server
{
    const DEFAULT_ADDRESS = '0.0.0.0';

    private $factory;
    private $encoder;
    private $accessLayer;
    private $servers = [];
    private $handlers = [];
    private $tls = false;

    public function __construct(AccessLayer $accessLayer = null, Encoder $encoder = null, ServerFactory $factory = null)
    {
        $this->factory = $factory ?: new DefaultServerFactory();
        $this->encoder = $encoder ?: new Rfc3977Encoder();
        $this->accessLayer = $accessLayer;
    }

    /**
     * Starts the server and begins listening for connections.
     */
    public function listen(int $port = 119, string $address = self::DEFAULT_ADDRESS, array $options = [])
    {
        // Create a server for the requested address and port.
        $server = $this->factory->create($address, $port, $options);
        $this->servers[] = $server;

        $coroutine = new Coroutine($this->accept($server));
        $coroutine->done();
    }

    /**
     * Shuts down the server and closes all ports.
     */
    public function close()
    {
        foreach ($this->servers as $server) {
            $server->close();
        }
    }

    /**
     * Gets a command handler instance.
     *
     * Handler instances are lazy-instantiated.
     */
    public function getHandler(string $handler): Handler
    {
        if (!isset($this->handlers[$handler])) {
            if (!class_exists($handler) || !class_implements($handler, Handler::class)) {
                throw new \RuntimeException("No handler found for '$handler'.");
            }

            $instance = new $handler();
            $this->handlers[$handler] = $instance;
        } else {
            $instance = $this->handlers[$handler];
        }

        return $instance;
    }

    /**
     * Accepts incoming connections as they are made.
     */
    private function accept(SocketServer $server): Generator
    {
        yield from log()->log(
            Log::INFO,
            'NNTP server listening on %s:%d',
            $server->getAddress(),
            $server->getPort()
        );

        while ($server->isOpen()) {
            // Wait for a client to connect.
            $socket = yield from $server->accept();

            // Handle the client in a separate coroutine.
            $coroutine = new Coroutine($this->handleClient($socket));
            $coroutine->done();
        }
    }

    /**
     * Services requests for a connected client.
     *
     * Manages the server-side state for the remote client and interprets client commands.
     */
    private function handleClient(Socket $socket): Generator
    {
        yield from log()->log(
            Log::INFO,
            'Accepted client from %s:%d on %s:%d',
            $socket->getRemoteAddress(),
            $socket->getRemotePort(),
            $socket->getLocalAddress(),
            $socket->getLocalPort()
        );

        // Create a new context object for this client.
        $context = new ClientContext($socket, $this->encoder, $this->accessLayer);

        try {
            // Send a welcome message.
            $welcome = new Response(200, 'Ready');
            yield from $context->writeResponse($welcome);

            // Command loop
            while ($socket->isOpen()) {
                // Parse incoming commands.
                $command = yield from $context->readCommand();

                // Determine the command name and choose how to handle it.
                $handler = null;
                switch ($command->name()) {
                    // Mandatory commands
                    case 'CAPABILITIES':
                        $handler = $this->getHandler(handlers\CapabilitiesHandler::class);
                        break;
                    case 'HEAD':
                        $handler = $this->getHandler(handlers\HeadHandler::class);
                        break;
                    case 'HELP':
                        $handler = $this->getHandler(handlers\HelpHandler::class);
                        break;
                    // We don't advertise MODE support, but some readers need it
                    case 'MODE':
                        $handler = $this->getHandler(handlers\ModeHandler::class);
                        break;
                    case 'STAT':
                        $handler = $this->getHandler(handlers\StatHandler::class);
                        break;
                    case 'QUIT':
                        yield from $context->writeResponse(new Response(205, 'Closing connection'));
                        break 2; // stop command loop

                    // LIST commands
                    case 'LIST':
                        $handler = $this->getHandler(handlers\ListHandler::class);
                        break;

                    // NEWNEWS commands
                    case 'NEWNEWS':
                        $handler = $this->getHandler(handlers\NewNewsHandler::class);
                        break;

                    // POST commands
                    case 'POST':
                        $handler = $this->getHandler(handlers\PostHandler::class);
                        break;

                    // READER commands
                    case 'ARTICLE':
                        $handler = $this->getHandler(handlers\ArticleHandler::class);
                        break;
                    case 'BODY':
                        $handler = $this->getHandler(handlers\BodyHandler::class);
                        break;
                    case 'DATE':
                        $handler = $this->getHandler(handlers\DateHandler::class);
                        break;
                    case 'GROUP':
                        $handler = $this->getHandler(handlers\GroupHandler::class);
                        break;
                    case 'LAST':
                        $handler = $this->getHandler(handlers\LastHandler::class);
                        break;
                    case 'LISTGROUP':
                        $handler = $this->getHandler(handlers\ListGroupHandler::class);
                        break;
                    case 'NEWGROUPS':
                        $handler = $this->getHandler(handlers\NewGroupsHandler::class);
                        break;
                    case 'NEXT':
                        $handler = $this->getHandler(handlers\NextHandler::class);
                        break;
                }

                // Unknown command
                if (!$handler) {
                    yield from $context->writeResponse(new Response(500, 'Unknown command'));
                } else {
                    // Execute the selected command handler.
                    try {
                        yield from $handler->handle($command, $context);
                    } catch (\Throwable $e) {
                        yield from log()->log(Log::ERROR, 'Error handling command: ' . $e);
                        yield from $context->writeResponse(new Response(502, 'Server error'));
                    }
                }
            }
        } catch (UnreadableException $e) {
            // Client disconnected.
        } catch (UnwritableException $e) {
            // Client disconnected.
        } catch (ClosedException $e) {
            // Client disconnected.
        }

        yield from log()->log(
            Log::INFO,
            'Disconnected client from %s:%d on %s:%d',
            $socket->getRemoteAddress(),
            $socket->getRemotePort(),
            $socket->getLocalAddress(),
            $socket->getLocalPort()
        );

        // Close the connection to the client.
        if ($socket->isOpen()) {
            $socket->close();
        }
    }
}
