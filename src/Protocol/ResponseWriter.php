<?php
namespace LibNNTP\Protocol;

use Icicle\Stream\WritableStream;


class ResponseWriter
{
    public function writeResponse(WritableStream $stream, Response $response): \Generator
    {
        // Send the code and message.
        yield from $stream->write($response->code() . ' ' . $response->message() . "\r\n");

        // If additional data lines are provided, send those also.
        if (!empty($response->data())) {
            yield from $stream->write($response->data() . "\r\n.\r\n");
        }
    }
}
