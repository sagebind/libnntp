<?php
namespace nntp\server;

use Generator;
use nntp\{Article, Group};


/**
 * An interface for a general data access layer for groups and articles used by the server.
 */
interface AccessLayer
{
    /**
     * Gets all available groups.
     */
    public function getGroups(): Generator;

    /**
     * Gets a group by name.
     */
    public function getGroupByName(string $name): Generator;

    /**
     * Gets an article by its unique message ID.
     */
    public function getArticleById(string $id): Generator;

    /**
     * Gets an article by its number in a group.
     */
    public function getArticleByNumber(string $group, int $number): Generator;

    /**
     * Gets the next article following the given article, by number, in the group.
     */
    public function getNextArticle(string $group, int $number): Generator;

    /**
    * Gets the previous article before the given article, by number, in the group.
    */
    public function getPreviousArticle(string $group, int $number): Generator;

    /**
     * Posts an article to a group.
     */
    public function postArticle(string $group, Article $article): Generator;
}
