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

    public static function success(int $statusCode, ?array $data = null): self
    {
        return new Response(
            $statusCode,
            ['data' => $data]
        );
    }

    public static function failed(int $statusCode, string $message, string $errorCode, ?array $details): self
    {
        $error = [
            'message' => $message,
            'code' => $errorCode
        ];

        if (!empty($details)) {
            $error['details'] = $details;
        }

        return new Response(
            $statusCode,
            [
                'error' => $error
            ]
        );
    }
}
