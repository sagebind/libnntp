<?php
namespace nntp\server\handlers;

use Generator;
use nntp\protocol\Command;
use nntp\protocol\Response;
use nntp\server\AccessUtil;
use nntp\server\ClientContext;

class HeadHandler implements Handler
{
    use ArticleHandlerTrait;

    public function handle(Command $command, ClientContext $context): Generator
    {
        $article = yield from $this->fetchArticle($command, $context);

        if ($article) {
            yield from $context->writeResponse(new Response(221, '%d %s Headers follow (multi-line)', $article->number(), $article->id()));
            yield from $context->writeData((string)$article->headers());
        }
    }
}
