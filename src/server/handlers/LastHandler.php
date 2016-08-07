<?php
namespace nntp\server\handlers;

use Generator;
use nntp\protocol\{Command, Response};
use nntp\server\ClientContext;


class LastHandler implements Handler
{
    public function handle(Command $command, ClientContext $context): Generator
    {
        // Make sure we have a valid cursor.
        if (!$context->getCursor()->valid()) {
            yield from $context->writeResponse(new Response(412, 'No newsgroup selected'));
            return;
        }

        // Try to move to the previous article.
        if (!yield from $context->getCursor()->previous()) {
            yield from $context->writeResponse(new Response(422, 'No previous article in this group'));
            return;
        }

        // Spit out the current article info.
        $article = $context->getCursor()->getArticle();
        yield from $context->writeResponse(new Response(223, '%d %s Article found', $article->number(), $article->id()));
    }
}
