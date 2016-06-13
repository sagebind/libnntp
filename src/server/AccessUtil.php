<?php
namespace nntp\server;

use Generator;
use nntp\protocol\Response;


final class AccessUtil
{
    public static function fetchArticleFromArgs(ClientContext $context, string ...$args): Generator
    {
        // Current article?
        if (count($args) === 0) {
            if ($context->getCurrentGroup() === null) {
                yield from $context->writeResponse(new Response(412, 'No newsgroup selected'));
                return;
            }

            $article = yield from $context->getAccessLayer()->getArticleByNumber($context->getCurrentGroup(), $context->getCurrentArticle());
            if (!$article) {
                yield from $context->writeResponse(new Response(420, 'Current article number is invalid'));
                return;
            }
        }

        // Article number?
        elseif (is_numeric($args[0])) {
            $number = (int)$args[0];

            if ($context->getCurrentGroup() === null) {
                yield from $context->writeResponse(new Response(412, 'No newsgroup selected'));
                return;
            }

            $article = yield from $context->getAccessLayer()->getArticleByNumber($context->getCurrentGroup(), $number);
            if (!$article) {
                yield from $context->writeResponse(new Response(423, 'No article with that number'));
                return;
            }
        }

        // Article ID
        else {
            $article = yield from $context->getAccessLayer()->getArticleById($args[0]);
            if (!$article) {
                yield from $context->writeResponse(new Response(430, 'No article with that message-id'));
                return;
            }
        }

        return $article;
    }
}
