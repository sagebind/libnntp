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
     * Checks if posting is currently allowed.
     */
    public function isPostingAllowed(): bool;

    /**
     * Gets all available groups.
     */
    public function getGroups(): Generator;

    /**
     * Gets a group by name.
     */
    public function getGroupByName(string $name): Generator;

    /**
     * Get a group cursor starting at the beginning of a group.
     */
    public function getGroupCursor(string $name): Generator;

    /**
     * Gets an article by its unique message ID.
     */
    public function getArticleById(string $id): Generator;

    /**
     * Gets an article by its number in a group.
     */
    public function getArticleByNumber(string $group, int $number): Generator;

    /**
     * Posts an article to a group.
     */
    public function postArticle(string $group, Article $article): Generator;
}
