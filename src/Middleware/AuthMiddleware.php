<?php

namespace Fatemeh\TaskManagerApi\Middleware;

use Fatemeh\TaskManagerApi\Http\Request;
use Fatemeh\TaskManagerApi\Http\Response;
use Fatemeh\TaskManagerApi\Logging\LoggerInterface;

class AuthMiddleware implements MiddlewareInterface
{
    public function __construct(private LoggerInterface $logger) {}

    public function handle(Request $request, callable $next): Response
    {
        if (is_null($request->getHeader('Authorization'))) {
            $this->logger->log('error', 'Authorization failed!', ['status_code' => 401]);
            return new Response(401, ['message' => 'Authorization failed']);
        }

        return $next($request);
    }
}
