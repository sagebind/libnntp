<?php
namespace nntp\server\handlers;

use Generator;
use nntp\Group;
use nntp\protocol\{Command, Response};
use nntp\server\ClientContext;


class ListHandler implements Handler
{
    public function handle(Command $command, ClientContext $context): Generator
    {
        yield from $context->writeResponse(new Response(215, 'List of newsgroups follows'));

        $groups = yield from $context->getAccessLayer()->getGroups();

        $data = array_reduce($groups, function(string $s, Group $group) {
            switch ($group->status()) {
                case Group::POSTING_PERMITTED:
                    $status = 'y';
                    break;
                case Group::POSTING_NOT_PERMITTED:
                    $status = 'n';
                    break;
                case Group::POSTING_FORWARDED:
                    $status = 'm';
                    break;
                default:
                    $status = '-';
                    break;
            }

            return $s . sprintf("%s %d %d %s\r\n",
                $group->name(),
                $group->highWaterMark(),
                $group->lowWaterMark(),
                $status);
        }, '');

        yield from $context->writeData($data);
    }
}
