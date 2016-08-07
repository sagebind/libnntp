<?php
require dirname(__DIR__) . '/vendor/autoload.php';

use Icicle\Loop;
use nntp\server\{AccessLayer, Server};
use nntp\{Article, Group};


$server = new Server(new class implements AccessLayer {
    private $data;

    public function __construct()
    {
        $this->data = [
            'php.internals' => [
                new Article('hello all!', [
                    'subject' => 'hello'
                ])
            ]
        ];
    }

    public function getGroups(): Generator
    {
        $groups = [];

        foreach ($this->data as $name => $articles) {
            $groups[] = new Group($name, count($articles), 1, count($articles), Group::POSTING_PERMITTED);
        }

        return $groups;
        yield;
    }

    public function getGroupByName(string $name): Generator
    {
        if (isset($this->data[$name])) {
            $articles = $this->data[$name];
            return new Group($name, count($articles), 1, count($articles), Group::POSTING_PERMITTED);
        } else {
            return;
        }
        yield;
    }

    public function getArticleById(string $id): Generator
    {
        yield;
    }

    public function getArticleByNumber(string $group, int $number): Generator
    {
        if (isset($this->data[$group]) && isset($this->data[$group][$number - 1])) {
            return $this->data[$group][$number - 1];
        } else {
            return;
        }
        yield;
    }

    public function getNextArticle(string $group, int $number): Generator
    {
        yield;
    }

    public function getPreviousArticle(string $group, int $number): Generator
    {
        yield;
    }

    public function postArticle(string $group, Article $article): Generator
    {
        yield;
    }
});
$server->listen(1190);

Loop\run();
