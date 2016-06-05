<?php
namespace nntp;


class Article
{
    private $headers;
    private $body;
    private $number;

    /**
     * Parses an article from a string.
     */
    public static function parse(string $data): self
    {
        $separator = strpos($data, "\r\n\r\n");

        if ($separator === false) {
            throw new FormatException('Invalid article format');
        }

        $headers = HeaderBag::parse(substr($data, 0, $separator + 2));
        $body = substr($data, $separator + 4);

        return new self($body, $headers);
    }

    public function __construct(string $body, HeaderBag $headers = null, int $number = 0)
    {
        $this->headers = $headers ?: new HeaderBag();
        $this->body = $body;
        $this->number = $number;
    }

    public function headers(): HeaderBag
    {
        return $this->headers;
    }

    public function number(): int
    {
        return $this->number;
    }

    public function id(): string
    {
        return $this->headers->get('message-id');
    }

    public function from(): string
    {
        return $this->headers->get('from');
    }

    public function group(): string
    {
        return $this->headers->get('newsgroups');
    }

    public function subject(): string
    {
        return $this->headers->get('subject');
    }

    public function body(): string
    {
        return $this->body;
    }

    public function __toString()
    {
        return (string)$this->headers . "\r\n" . $this->body;
    }
}
