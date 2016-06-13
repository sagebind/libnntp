<?php
namespace nntp\server\handlers;

use Generator;
use nntp\protocol\{Command, Response};
use nntp\server\{AccessUtil, ClientContext};


class BodyHandler implements Handler
{
    public function handle(Command $command, ClientContext $context): Generator
    {
        $article = yield from AccessUtil::fetchArticleFromArgs($context, ...$command->args());

        if ($article) {
            yield from $context->writeResponse(new Response(222, '%d %s Body follows (multi-line)', $article->number(), $article->id()));
            yield from $context->writeData($article->body());
        }
    }
}
