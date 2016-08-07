<?php
namespace nntp\server;

use Generator;
use nntp\{Article, Group};


/**
 * Current article position in a group.
 */
interface GroupCursor
{
    /**
     * Checks if the current cursor position is valid.
     */
    public function valid(): bool;

    /**
     * Get the currently selected group.
     */
    public function getGroup(): Group;

    /**
     * Get the currently selected article.
     */
    public function getArticle(): Article;

    /**
     * Moves to the next article in the current group.
     *
     * Resolves with true if successful, or false if there is no next item.
     */
    public function next(): Generator;

    /**
     * Moves to the previous article in the current group.
     *
     * Resolves with true if successful, or false if there is no previous item.
     */
    public function previous(): Generator;

    /**
     * Moves to the specified article number in the current group.
     *
     * Resolves with true if successful, or false if there is no previous item.
     */
    public function seek(int $number): Generator;
}
