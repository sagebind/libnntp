<?php
namespace LibNNTP\Command;


interface Command
{
    public function name(): string;

    public function arguments(): array;

    public function isMultiLine(): bool;
}
