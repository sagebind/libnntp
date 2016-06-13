<?php
namespace nntp\server\handlers;

use Generator;
use nntp\protocol\{Command, Response};
use nntp\server\ClientContext;


class GroupHandler implements Handler
{
    public function handle(Command $command, ClientContext $context): Generator
    {
        if ($command->argCount() !== 1) {
            yield from $context->writeResponse(new Response(501, 'Invalid number of arguments'));
            return;
        }

        $name = $command->arg(0);
        $group = yield from $context->getAccessLayer()->getGroupByName($name);

        if (!$group) {
            yield from $context->writeResponse(new Response(411, 'No such newsgroup'));
            return;
        }

        $context->setCurrentGroup($group->name());
        $context->setCurrentArticle($group->lowWaterMark());
        yield from $context->writeResponse(new Response(211, $group->count() . ' ' . $group->lowWaterMark() . ' ' . $group->highWaterMark() . ' ' . $group->name() . ' Group successfully selected'));
    }
}
