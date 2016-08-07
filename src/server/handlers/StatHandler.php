<?php
namespace nntp\server\handlers;

use Generator;
use nntp\protocol\{Command, Response};
use nntp\server\{AccessUtil, ClientContext};


class StatHandler implements Handler
{
    use ArticleHandlerTrait;

    public function handle(Command $command, ClientContext $context): Generator
    {
        $article = yield from $this->fetchArticleFromArgs($context, ...$command->args());

        if ($article) {
            yield from $context->writeResponse(new Response(223, '%d %s Article exists', $article->number(), $article->id()));
        }
    }
}
