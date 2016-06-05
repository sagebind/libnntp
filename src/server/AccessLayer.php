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
     * Posts an article to a group.
     */
    public function postArticle(Group $group, Article $article): Generator;
}
