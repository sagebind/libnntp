<?php
namespace nntp\server\handlers;

use Generator;
use nntp\protocol\Command;
use nntp\protocol\Response;
use nntp\server\ClientContext;

trait ArticleHandlerTrait
{
    protected function fetchArticle(Command $command, ClientContext $context): Generator
    {
        // Current article?
        if ($command->argCount() === 0) {
            if (!$context->getCursor()->valid()) {
                yield from $context->writeResponse(new Response(412, 'No newsgroup selected'));
                return;
            }

            return $context->getCursor()->getArticle();
        }

        // Article number?
        elseif (is_numeric($command->arg(0))) {
            $number = (int)$command->arg(0);

            if (!$context->getCursor()->valid()) {
                yield from $context->writeResponse(new Response(412, 'No newsgroup selected'));
                return;
            }

            if (!yield from $context->getCursor()->seek($number)) {
                yield from $context->writeResponse(new Response(423, 'No article with that number'));
                return;
            }

            return $context->getCursor()->getArticle();
        }

        // Article ID
        else {
            $article = yield from $context->getAccessLayer()->getArticleById($command->arg(0));
            if (!$article) {
                yield from $context->writeResponse(new Response(430, 'No article with that message-id'));
                return;
            }
        }

        return $article;
    }
}
