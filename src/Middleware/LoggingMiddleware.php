<?php

namespace Fatemeh\TaskManagerApi\Middleware;

use Fatemeh\TaskManagerApi\Http\Request;
use Fatemeh\TaskManagerApi\Http\Response;
use Fatemeh\TaskManagerApi\Logging\LoggerInterface;

class LoggingMiddleware implements MiddlewareInterface
{
    public function __construct(private LoggerInterface $logger) {}

    public function handle(Request $request, callable $next): Response
    {
        $this->logger->log('Info', "Incoming request", ['method' => $request->method, 'path' => $request->path]);

        /** @var Response $response */
        $response = $next($request);

        $this->logger->log('Info', "Outgoing response", ['status_code' => $response->statusCode]);

        return $response;
    }
}
