<?php
namespace nntp\server\handlers;

use Generator;
use nntp\protocol\Command;
use nntp\protocol\Response;
use nntp\server\ClientContext;
use nntp\server\NotFoundException;

class GroupHandler implements Handler
{
    public function handle(Command $command, ClientContext $context): Generator
    {
        if ($command->argCount() !== 1) {
            yield from $context->writeResponse(new Response(501, 'Invalid number of arguments'));
            return;
        }

        try {
            $name = $command->arg(0);
            $cursor = yield from $context->getAccessLayer()->getGroupCursor($name);
            $context->setCursor($cursor);
        } catch (NotFoundException $e) {
            yield from $context->writeResponse(new Response(411, 'No such newsgroup'));
            return;
        }

        $group = $context->getCursor()->getGroup();
        yield from $context->writeResponse(new Response(211, $group->count() . ' ' . $group->lowWaterMark() . ' ' . $group->highWaterMark() . ' ' . $group->name() . ' Group successfully selected'));
    }
}
