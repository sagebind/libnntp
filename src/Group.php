<?php
namespace LibNNTP;


class Group
{
    const STATUS_UNKNOWN = 0;
    const POSTING_PERMITTED = 1;
    const POSTING_NOT_PERMITTED = 2;
    const POSTING_FORWARDED = 3;

    private $name;
    private $high;
    private $low;
    private $status;

    public function __construct(string $name, int $high, int $low, int $status = 0)
    {
        $this->name = $name;
        $this->high = $high;
        $this->low = $low;
        $this->status = $status;
    }

    public function name(): string
    {
        return $this->name;
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
