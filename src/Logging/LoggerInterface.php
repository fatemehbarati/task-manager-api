<?php

namespace Fatemeh\TaskManagerApi\Logging;

interface LoggerInterface
{
    public function log(
        string $level,
        string $message,
        array $context = []
    ): void;
}
