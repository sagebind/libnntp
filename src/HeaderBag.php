<?php
namespace nntp;

use Countable;
use Iterator;
use IteratorAggregate;


/**
 * A collection of article headers.
 */
class HeaderBag implements Countable, IteratorAggregate
{
    private $headers = [];

    /**
     * Parses headers from a string.
     */
    public static function parse(string $data): self
    {
        if (preg_match_all('/([^:\r\n]+):\s+([^\r\n]*(\r\n[ \t][^\r\n]+)*)\r\n/', $data, $matches, PREG_SET_ORDER) === false) {
            throw new FormatException('Invalid header format');
        }

        foreach ($matches as $match) {
            // Undo header folding.
            $value = preg_replace('/\r\n[ \t]([^\r\n]+)/', ' $1', $match[2]);

            $headers[$match[1]] = $value;
        }

        return new self($headers);
    }

    /**
     * Creates a new header bag with an array of headers.
     */
    public function __construct(array $headers = [])
    {
        $this->headers = array_change_key_case($headers, CASE_LOWER);
    }

    /**
     * Gets the number of headers.
     */
    public function count(): int
    {
        return count($this->headers);
    }

    /**
     * Checks if the header bag contains a header.
     */
    public function contains(string $name): bool
    {
        return isset($this->headers[strtolower($name)]);
    }

    /**
     * Gets the value of a header.
     */
    public function get(string $name): string
    {
        $name = strtolower($name);
        return isset($this->headers[$name]) ? $this->headers[$name] : '';
    }

    /**
     * Gets an iterator over the headers.
     */
    public function getIterator(): Iterator
    {
        foreach ($this->headers as $name => $value) {
            yield $name => $value;
        }
    }

    public function __toString(): string
    {
        $string = '';

        foreach ($this->headers as $name => $value) {
            $string .= $name . ': ' . $value . "\r\n";
        }

        return $string;
    }
}
