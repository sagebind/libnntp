<?php
namespace nntp\server;

use Generator;
use nntp\{Article, Group};


class InvalidCursor implements GroupCursor
{
    public function valid(): bool
    {
        return false;
    }

    public function getGroup(): Group
    {
        throw new \RuntimeException();
    }

    public function getArticle(): Article
    {
        throw new \RuntimeException();
    }

    public function next(): Generator
    {
        return false;
        yield;
    }

    public function previous(): Generator
    {
        return false;
        yield;
    }

    public function seek(int $number): Generator
    {
        return false;
        yield;
    }
}
