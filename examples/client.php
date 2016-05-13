<?php
require dirname(__DIR__) . '/vendor/autoload.php';

use Icicle\Coroutine;
use Icicle\Loop;
use LibNNTP\Client;


Coroutine\create(function () {
    $client = new Client();
    yield from $client->connect('news.php.net');
    var_dump(yield from $client->getGroups());
    var_dump(yield from $client->chooseGroup('php.internals'));
    yield from $client->close();
})->done();

Loop\run();
