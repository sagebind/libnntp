<?php
namespace nntp\server\handlers;

use Generator;
use nntp\protocol\{Command, Response};
use nntp\server\ClientContext;


class HelpHandler implements Handler
{
    public function handle(Command $command, ClientContext $context): Generator
    {
        yield from $context->writeResponse(new Response(100, 'Help text follows (multi-line)'));

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

        yield from $context->writeData($data);
    }
}
