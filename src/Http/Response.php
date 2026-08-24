<?php

namespace Fatemeh\TaskManagerApi\Http;

class Response
{
    public function __construct(
        public readonly int $statusCode,
        public readonly array $body
    ) {}

    public function send(): void
    {
        http_response_code($this->statusCode);
        header('Content-Type: application/json');
        echo json_encode($this->body);
    }
}
