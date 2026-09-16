<?php
namespace Fatemeh\TaskManagerApi\Exceptions;

class UnauthorizedException extends ApiException {
    public function __construct(string $message = "Invalid or expired token")
    {
        parent::__construct($message, 401, ErrorCode::Unauthorized);
    }
}