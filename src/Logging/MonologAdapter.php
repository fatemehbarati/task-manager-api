<?php

namespace Fatemeh\TaskManagerApi\Logging;

use Monolog\Level;
use Monolog\Logger;

class MonologAdapter implements LoggerInterface
{
    public function __construct(private Logger $logger) {}

    public function log(string $level, string $message, array $context = []): void
    {
        $this->logger->log(Level::fromName(ucfirst(strtolower($level))), $message, $context);
    }
}
