<?php
namespace Fatemeh\TaskManagerApi\Exceptions;

use Exception;

class ApiException extends Exception {
    private int $statusCode;
    private ErrorCode $errorCode;

    public function __construct(string $message, int $statusCode, ErrorCode $errorCode)
    {
        parent::__construct($message);
        $this->statusCode = $statusCode;
        $this->errorCode = $errorCode;
    }

    public function getStatusCode() : int {
        return $this->statusCode;
    }

    public function getErrorCode() : string {
        return $this->errorCode->value;
    }
}