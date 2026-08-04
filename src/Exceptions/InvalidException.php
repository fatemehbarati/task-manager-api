<?php
namespace Fatemeh\TaskManagerApi\Exceptions;

class InvalidException extends ApiException {
    public function __construct(string $message = "Input is not valid!")
    {
        parent::__construct($message, 400);
    }
}