<?php
namespace nntp\server\handlers;

use Generator;
use nntp\protocol\{Command, Response};
use nntp\server\{AccessUtil, ClientContext};


class HeadHandler implements Handler
{
    public function handle(Command $command, ClientContext $context): Generator
    {
        $article = yield from AccessUtil::fetchArticleFromArgs($context, ...$command->args());

        if ($article) {
            yield from $context->writeResponse(new Response(221, '%d %s Headers follow (multi-line)', $article->number(), $article->id()));
            yield from $context->writeData((string)$article->headers());
        }
    }
}
