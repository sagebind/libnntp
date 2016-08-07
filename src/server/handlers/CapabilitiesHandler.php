<?php
namespace nntp\server\handlers;

use Generator;
use nntp\protocol\{Command, Response};
use nntp\server\ClientContext;


class CapabilitiesHandler implements Handler
{
    public function handle(Command $command, ClientContext $context): Generator
    {
        yield from $context->writeResponse(new Response(101, 'Capability list follows (multi-line)'));

        // Generate the list of capabilities that we support.
        $capabilities = [
            'VERSION 2',
            'READER',
            'POST',
            'NEWNEWS',
            'LIST ACTIVE',
            'IMPLEMENTATION coderstephen/nntp server',
        ];

        // If TLS is not already active, the STARTTLS is available.
        if (!$context->getSocket()->isCryptoEnabled()) {
            //array_splice($capabilities, -2, 0, ['STARTTLS']);
        }

        $data = implode("\r\n", $capabilities);
        yield from $context->writeData($data);
    }
}
