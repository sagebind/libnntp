<?php
namespace LibNNTP\Protocol;

use Icicle\Stream;
use Icicle\Stream\ReadableStream;


class ResponseReader
{
    public function readResponse(ReadableStream $stream, bool $multiLine = false): \Generator
    {
        $line = yield from Stream\readUntil($stream, "\r\n");

        if (preg_match('/^(\d\d\d)\s+/', $line, $matches) !== 1) {
            throw new \Exception();
        }

        // Create a response object.
        $response = new Response((int)$matches[1], substr($line, 4));

        // If there is more to read, read the remaining message.
        if ($multiLine && $response->isOk()) {
            $lines = yield from Stream\readUntil($stream, "\r\n.\r\n");
            $response = new Response($response->code(), $response->message(), $lines);
        }

        return $response;
    }
}
