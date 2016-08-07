<?php
namespace nntp;

use Countable;


class Group implements Countable
{
    const STATUS_UNKNOWN = 0;
    const POSTING_PERMITTED = 1;
    const POSTING_NOT_PERMITTED = 2;
    const POSTING_FORWARDED = 3;

    private $name;
    private $count;
    private $low;
    private $high;
    private $status;

    public function __construct(string $name, int $count, int $low, int $high, int $status = 0)
    {
        if ($low > $high) {
            throw new \DomainException('Low water mark cannot be greater than high water mark.');
        }

        $this->name = $name;
        $this->count = $count;
        $this->low = $low;
        $this->high = $high;
        $this->status = $status;
    }

    public function name(): string
    {
        return $this->name;
    }

    /**
     * Gets the number of articles in the group.
     */
    public function count(): int
    {
        return $this->count;
    }

    public function lowWaterMark(): int
    {
        return $this->low;
    }

    public function highWaterMark(): int
    {
        return $this->high;
    }

    public function status()
    {
        return $this->status;
    }
}
