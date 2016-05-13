<?php
namespace LibNNTP\Protocol;

use Icicle\Stream;
use Icicle\Stream\ReadableStream;


class CommandReader
{
    public function readCommand(ReadableStream $stream)
    {
        $line = yield from Stream\readUntil($stream, "\r\n");
        $args = preg_split('/\s+/', $line);

        if (count($args) === 0) {
            $command = '';
        } else {
            $command = array_shift($args);
        }

        return new Command($command, $args);
    }
}
