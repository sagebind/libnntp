<?php
namespace nntp\server\handlers;

use Generator;
use nntp\protocol\{Command, Response};
use nntp\server\ClientContext;


class DateHandler implements Handler
{
    public function handle(Command $command, ClientContext $context): Generator
    {
        yield from $context->writeResponse(new Response(111, date('YmdHis')));
    }
}
