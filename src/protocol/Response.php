<?php
namespace nntp\protocol;


class Response
{
    private $code;
    private $message;

    public function __construct(int $code, string $message, ...$args)
    {
        $this->code = $code;

        if (!empty($args)) {
            $this->message = sprintf($message, ...$args);
        } else {
            $this->message = $message;
        }
    }

    public function code(): int
    {
        return $this->code;
    }

    public function message(): string
    {
        return $this->message;
    }

    public function isOk(): bool
    {
        return $this->code < 400;
    }

    public function __toString()
    {
        return $this->code . ' ' . $this->message;
    }
}
