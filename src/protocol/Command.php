<?php
namespace nntp\protocol;


class Command
{
    private $name;
    private $args;

    public function __construct(string $name, string ...$args)
    {
        $this->name = strtoupper($name);
        $this->args = $args;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function args(): array
    {
        return $this->args;
    }

    public function argCount(): int
    {
        return count($this->args);
    }

    public function arg(int $index): string
    {
        if ($index >= count($this->args)) {
            throw new \OutOfRangeException();
        }

        return $this->args[$index];
    }
}
