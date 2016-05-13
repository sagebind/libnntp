<?php
namespace LibNNTP\Protocol;

use Icicle\Stream\WritableStream;


class CommandWriter
{
    public function writeCommand(WritableStream $stream, Command $command): \Generator
    {
        yield from $stream->write($command->name());

        foreach ($command->arguments() as $arg) {
            yield from $stream->write(' ' . $arg);
        }

        yield from $stream->write("\r\n");
    }
}
