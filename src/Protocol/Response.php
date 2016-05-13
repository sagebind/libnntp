<?php
namespace LibNNTP\Protocol;


class Response
{
    private $code;
    private $message;
    private $data;

    public function __construct(int $code, string $message, string $data = '')
    {
        $this->code = $code;
        $this->message = $message;
        $this->data = $data;
    }

    public function code(): int
    {
        return $this->code;
    }

    public function message(): string
    {
        return $this->message;
    }

    public function data(): string
    {
        return $this->data;
    }

    public function isOk(): bool
    {
        return $this->code >= 200 && $this->code < 400;
    }
}
