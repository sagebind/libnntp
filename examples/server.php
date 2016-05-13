<?php
require dirname(__DIR__) . '/vendor/autoload.php';

use Icicle\Loop;
use LibNNTP\Server;


$server = new Server();
$server->listen(1190);

Loop\run();
