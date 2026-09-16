<?php
namespace Fatemeh\TaskManagerApi\Exceptions;

class ValidationException extends ApiException {
    private array $errors;

    public function __construct(array $errors = [])
    {
        $this->errors = $errors;
        parent::__construct("Validation failed.", 422, ErrorCode::ValidationError);
    }

    public function getErrors(): array {
        return $this->errors;
    }
}