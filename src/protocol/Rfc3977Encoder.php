<?php
namespace nntp\protocol;

use Generator;
use Icicle\Stream;
use Icicle\Stream\{ReadableStream, WritableStream};
use nntp\FormatException;


class Rfc3977Encoder implements Encoder
{
    public function readCommand(ReadableStream $stream): Generator
    {
        $line = yield from Stream\readUntil($stream, "\r\n");
        $args = preg_split('/\s+/', trim($line));

        if (count($args) === 0) {
            $command = '';
        } else {
            $command = array_shift($args);
        }

        return new Command($command, ...$args);
    }

    public function writeCommand(WritableStream $stream, Command $command): Generator
    {
        yield from $stream->write($command->name());

        foreach ($command->args() as $arg) {
            yield from $stream->write(' ' . $arg);
        }

        yield from $stream->write("\r\n");
    }

    public function readResponse(ReadableStream $stream): Generator
    {
        $line = yield from Stream\readUntil($stream, "\r\n");

        if (preg_match('/^(\d\d\d)\s+/', $line, $matches) !== 1) {
            throw new FormatException("Invalid response format");
        }

        return new Response((int)$matches[1], substr($line, 4, -2));
    }

    public function writeResponse(WritableStream $stream, Response $response): Generator
    {
        yield from $stream->write($response->code() . ' ' . $response->message() . "\r\n");
    }

    public function readData(ReadableStream $stream): Generator
    {
        // Read the entire data block.
        $raw = yield from Stream\readUntil($stream, "\r\n.\r\n");

        // Undo dot-stuffing.
        $data = str_replace("\r\n..\r\n", "\r\n.\r\n", $raw);

        // Return the data portion.
        return substr($data, 0, -5);
    }

    public function writeData(WritableStream $stream, string $data): Generator
    {
        // Apply "dot-stuffing" to problematic lines.
        $raw = str_replace("\r\n.\r\n", "\r\n..\r\n", $data);

        // Send the data along with a terminator.
        yield from $stream->write($raw . "\r\n.\r\n");
    }
}
