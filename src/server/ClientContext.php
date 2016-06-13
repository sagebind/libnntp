<?php
namespace nntp\server;

use Generator;
use Icicle\Stream\DuplexStream;
use Icicle\Stream\Exception\UnreadableException;
use nntp\Group;
use nntp\protocol\{Command, Encoder, Response};
use nntp\server\handlers\Handler;


class ClientContext
{
    private $stream;
    private $encoder;
    private $currentGroup;
    private $currentArticle;

    public function __construct(DuplexStream $stream, Encoder $encoder, AccessLayer $accessLayer)
    {
        $this->stream = $stream;
        $this->encoder = $encoder;
        $this->accessLayer = $accessLayer;
    }

    public function getCurrentGroup()
    {
        return $this->currentGroup;
    }

    public function setCurrentGroup(string $group)
    {
        $this->currentGroup = $group;
    }

    public function getCurrentArticle()
    {
        return $this->currentArticle;
    }

    public function setCurrentArticle(int $articleNumber)
    {
        $this->currentArticle = $articleNumber;
    }

    public function getAccessLayer(): AccessLayer
    {
        return $this->accessLayer;
    }

    public function readCommand(): Generator
    {
        return $this->encoder->readCommand($this->stream);
    }

    public function writeResponse(Response $response): Generator
    {
        return $this->encoder->writeResponse($this->stream, $response);
    }

    public function readData(): Generator
    {
        return $this->encoder->readData($this->stream);
    }

    public function writeData(string $data): Generator
    {
        return $this->encoder->writeData($this->stream, $data);
    }
}
