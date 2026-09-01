<?php

namespace Fatemeh\TaskManagerApi\Middleware;

use Fatemeh\TaskManagerApi\Exceptions\UnauthorizedException;
use Fatemeh\TaskManagerApi\Http\Request;
use Fatemeh\TaskManagerApi\Http\Response;
use Fatemeh\TaskManagerApi\Logging\LoggerInterface;
use Fatemeh\TaskManagerApi\Services\JwtService;

class AuthMiddleware implements MiddlewareInterface
{
    public function __construct(private LoggerInterface $logger, private JwtService $jwtService) {}

    public function handle(Request $request, callable $next): Response
    {
        $headerAuthorization = $request->getHeader('Authorization');
        if (is_null($headerAuthorization) || !str_starts_with($headerAuthorization, 'Bearer ')) {
            $this->logger->log('error', 'Authorization failed!', ['status_code' => 401]);
            throw new UnauthorizedException();
        }

        $accessToken = explode(' ', $request->getHeader('Authorization'))[1];
        $this->jwtService->validateAccessToken($accessToken);

        return $next($request);
    }
}
