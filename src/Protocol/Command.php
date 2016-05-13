<?php
namespace LibNNTP\Protocol;


class Command
{
    private $name;
    private $arguments;
    private $multiLine;

    public function __construct(string $name, array $arguments = [], bool $multiLine = false)
    {
        $this->name = strtoupper($name);
        $this->arguments = $arguments;
        $this->multiLine = $multiLine;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function arguments(): array
    {
        return $this->arguments;
    }

    public function isMultiLine(): bool
    {
        return $this->multiLine;
    }
}
