<?php
namespace nntp\server\handlers;

use Generator;
use nntp\protocol\{Command, Response};
use nntp\server\ClientContext;


class NextHandler implements Handler
{
    public function handle(Command $command, ClientContext $context): Generator
    {
        // Make sure we have a valid cursor.
        if (!$context->getCursor()->valid()) {
            yield from $context->writeResponse(new Response(412, 'No newsgroup selected'));
            return;
        }

        // Try to move to the next article.
        if (!yield from $context->getCursor()->next()) {
            yield from $context->writeResponse(new Response(421, 'No next article in this group'));
            return;
        }

        // Spit out the current article info.
        $article = $context->getCursor()->getArticle();
        yield from $context->writeResponse(new Response(223, '%d %s Article found', $article->number(), $article->id()));
    }
}
