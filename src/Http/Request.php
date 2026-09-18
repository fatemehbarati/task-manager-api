<?php

namespace Fatemeh\TaskManagerApi\Http;

use Fatemeh\TaskManagerApi\Exceptions\InvalidException;

class Request
{
    private array $headers;

    public function __construct(
        public readonly string $method,
        public readonly string $path,
        array $headers,
        public readonly array $body
    ) {
        $this->headers = array_change_key_case($headers, CASE_LOWER);
    }

    public function getHeader(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }

    public static function fromGlobals(): self
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
        $headers = getallheaders();
        $body = [];

        if (in_array($method, ['POST', 'PUT'])) {
            $body = json_decode(file_get_contents('php://input'), true);

            if (
                json_last_error() !== JSON_ERROR_NONE ||
                !is_array($body) ||
                (array_is_list($body) && !empty($body))
            ) {
                throw new InvalidException();
            }
        }

        return new self($method, $path, $headers, $body);
    }
}
