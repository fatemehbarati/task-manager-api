<?php
namespace Fatemeh\TaskManagerApi\Exceptions;

class InvalidException extends ApiException {
    public function __construct(string $message = "The request itself is malformed!")
    {
        parent::__construct($message, 400, ErrorCode::BadRequest);
    }
}