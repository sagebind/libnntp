<?php
namespace nntp\server\handlers;

use Generator;
use nntp\protocol\{Command, Response};
use nntp\server\ClientContext;


class ModeHandler implements Handler
{
    public function handle(Command $command, ClientContext $context): Generator
    {
        yield from $context->writeResponse(new Response(200, 'Reader mode already active'));
    }
}
