<?php
namespace nntp\protocol;

use Generator;
use Icicle\Stream\{ReadableStream, WritableStream};


interface Encoder
{
    public function readCommand(ReadableStream $stream): Generator;
    public function writeCommand(WritableStream $stream, Command $command): Generator;
    public function readResponse(ReadableStream $stream): Generator;
    public function writeResponse(WritableStream $stream, Response $response): Generator;
    public function readData(ReadableStream $stream): Generator;
    public function writeData(WritableStream $stream, string $data): Generator;
}
