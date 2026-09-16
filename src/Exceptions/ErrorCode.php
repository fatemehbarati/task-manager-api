<?php
namespace Fatemeh\TaskManagerApi\Exceptions;

enum ErrorCode: string {
    case NotFound = 'NOT_FOUND';
    case ValidationError = 'VALIDATION_ERROR';
    case Unauthorized = 'UNAUTHORIZED';
    case InternalError = 'INTERNAL_ERROR';
    case BadRequest = 'BAD_REQUEST';
}