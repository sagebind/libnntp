<?php
namespace nntp\server\handlers;

use Generator;
use nntp\Article;
use nntp\protocol\{Command, Response};
use nntp\server\ClientContext;


class PostHandler implements Handler
{
    public function handle(Command $command, ClientContext $context): Generator
    {
        if (!$context->getAccessLayer()->isPostingAllowed()) {
            yield from $context->writeResponse(new Response(440, 'Posting not permitted'));
            return;
        }

        yield from $context->writeResponse(new Response(340, 'Send article to be posted'));

        $data = yield from $context->readData();
        $article = Article::parse($data);

        try {
            yield from $context->getAccessLayer()->postArticle($article);
            yield from $context->writeResponse(new Response(240, 'Article received OK'));
        } catch (\Throwable $e) {
            yield from $context->writeResponse(new Response(441, 'Posting failed'));
        }

    }
}
