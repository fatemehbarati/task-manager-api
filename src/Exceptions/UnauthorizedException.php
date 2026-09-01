<?php
namespace Fatemeh\TaskManagerApi\Exceptions;

class UnauthorizedException extends ApiException {
    public function __construct()
    {
        parent::__construct("Invalid or expired token", 401);
    }
}