<?php
namespace nntp\server\handlers;

use Generator;
use nntp\protocol\{Command, Response};
use nntp\server\ClientContext;


class NewGroupsHandler implements Handler
{
    public function handle(Command $command, ClientContext $context): Generator
    {
    }
}
