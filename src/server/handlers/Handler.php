<?php
namespace nntp\server\handlers;

use Generator;
use nntp\protocol\Command;
use nntp\server\ClientContext;


interface Handler
{
    public function handle(Command $command, ClientContext $context): Generator;
}
