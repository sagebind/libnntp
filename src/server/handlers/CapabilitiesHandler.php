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
        $data = implode("\r\n", [
            'VERSION 2',
            'READER',
            'POST',
            'NEWNEWS',
            'LIST ACTIVE',
            'IMPLEMENTATION coderstephen/nntp server',
        ]);

        yield from $context->writeData($data);
    }
}
