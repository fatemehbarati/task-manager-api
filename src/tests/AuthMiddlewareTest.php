<?php
namespace Fatemeh\TaskManagerApi\tests;

use Fatemeh\TaskManagerApi\Exceptions\UnauthorizedException;
use Fatemeh\TaskManagerApi\Http\Request;
use Fatemeh\TaskManagerApi\Http\Response;
use Fatemeh\TaskManagerApi\Logging\LoggerInterface;
use Fatemeh\TaskManagerApi\Middleware\AuthMiddleware;
use Fatemeh\TaskManagerApi\Services\JwtService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AuthMiddleware::class)]
class AuthMiddlewareTest extends TestCase {
    private AuthMiddleware $authMiddleware;

    public function setUp(): void
    {
    }

    public function testHandleNullAuthorizationReturnsError(): void {
        $jwtServiceMock = $this->createMock(JwtService::class);
        $jwtServiceMock->expects($this->never())->method('validateAccessToken');

        $loggerInterfaceMock = $this->createMock(LoggerInterface::class);
        $loggerInterfaceMock->expects($this->once())
            ->method('log')->with('error', 'Authorization failed!', ['status_code' => 401]);

        $this->authMiddleware = new AuthMiddleware($loggerInterfaceMock, $jwtServiceMock);
        $request = new Request('method', 'path', ['Authorization' => ''], []);
        $next = function () {
            $this->fail('$next should not have been called when Authorization header is missing');
        };

        $this->expectException(UnauthorizedException::class);
        $this->authMiddleware->handle($request, $next);
    }

    public function testHandleAuthorizationPasses(): void {
        $jwtServiceMock = $this->createMock(JwtService::class);
        $jwtServiceMock->expects($this->once())
            ->method('validateAccessToken')->with('123')->willReturn(['sub' => 42]);

        $loggerInterfaceMock = $this->createMock(LoggerInterface::class);
        $loggerInterfaceMock->expects($this->never())->method('log');

        $this->authMiddleware = new AuthMiddleware($loggerInterfaceMock, $jwtServiceMock);
        $request = new Request('method', 'path', ['Authorization' => 'Bearer 123'], []);

        $nextCalled = false;
        $expectedResponse = new Response(200, ['message' => 'ok']);
        $next = function ($req) use (&$nextCalled, $expectedResponse, $request) {
            $nextCalled = true;
            $this->assertSame($request, $req);
            return $expectedResponse;
        };

        $result = $this->authMiddleware->handle($request, $next);

        $this->assertTrue($nextCalled, '$next should have been called for a valid token');
        $this->assertSame($expectedResponse, $result);
    }
}