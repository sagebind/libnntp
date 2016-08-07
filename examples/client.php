<?php
require dirname(__DIR__) . '/vendor/autoload.php';

use Icicle\Coroutine;
use Icicle\Loop;
use nntp\Client;


Coroutine\create(function () {
    $client = yield from Client::connect('localhost', 1190);
    //$client = yield from Client::connect('news.php.net', 119);

    //var_dump(yield from $client->getGroups());

    $group = yield from $client->setCurrentGroup('meta');

    $c = min(1, $group->count());
    for ($i = 0; $i < $c; ++$i) {
        $article = yield from $client->getArticleHeaders();
        var_dump($article);

        if ($i + 1 < $c) {
            yield from $client->next();
        }
    }

    var_dump(yield from $client->getArticle());

    yield from $client->close();
})->done();

Loop\run();
