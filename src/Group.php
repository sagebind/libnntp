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
    private $high;
    private $low;
    private $status;

    public function __construct(string $name, int $count, int $high, int $low, int $status = 0)
    {
        $this->name = $name;
        $this->count = $count;
        $this->high = $high;
        $this->low = $low;
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

    public function highWaterMark(): int
    {
        return $this->high;
    }

    public function lowWaterMark(): int
    {
        return $this->low;
    }

    public function status()
    {
        return $this->status;
    }
}
