<?php

namespace Fatemeh\TaskManagerApi;

use Fatemeh\TaskManagerApi\Exceptions\ApiException;
use Fatemeh\TaskManagerApi\Exceptions\ErrorCode;
use Fatemeh\TaskManagerApi\Exceptions\InvalidException;
use Fatemeh\TaskManagerApi\Exceptions\NotFoundException;
use Fatemeh\TaskManagerApi\Exceptions\UnauthorizedException;
use Fatemeh\TaskManagerApi\Exceptions\ValidationException;
use Fatemeh\TaskManagerApi\Http\Request;
use Fatemeh\TaskManagerApi\Http\Response;
use Fatemeh\TaskManagerApi\Middleware\AuthMiddleware;
use Fatemeh\TaskManagerApi\Middleware\LoggingMiddleware;
use Throwable;

class Router
{
    private array $router = [];

    public function __construct(private LoggingMiddleware $loggingMiddleware, private AuthMiddleware $authMiddleware) {}

    public function get(string $path, callable $handler, array $middleware = []): void
    {
        $this->addRouter('GET', $path, $handler, $middleware);
    }

    public function post(string $path, callable $handler, array $middleware = []): void
    {
        $this->addRouter('POST', $path, $handler, $middleware);
    }

    public function put(string $path, callable $handler, array $middleware = []): void
    {
        $this->addRouter('PUT', $path, $handler, $middleware);
    }

    public function delete(string $path, callable $handler, array $middleware = []): void
    {
        $this->addRouter('DELETE', $path, $handler, $middleware);
    }

    private function addRouter(string $method, string $path, callable $handler, array $middleware = []): void
    {
        $this->router[$method][$path] = [
            'handler' => $handler,
            'middleware' => $middleware
        ];
    }

    public function dispatch(): void
    {
        header('Content-Type: application/json');
        $request = Request::fromGlobals();
        try {
            $response = $this->loggingMiddleware->handle($request, $this->handleRequest(...));
            $response->send();
        } catch (ValidationException $e) {
            $this->respondWithError($e->getStatusCode(), $e->getErrorCode(), $e->getMessage(), $e->getErrors());
        } catch (InvalidException|NotFoundException|ApiException|UnauthorizedException $e) {
            $this->respondWithError($e->getStatusCode(), $e->getErrorCode(), $e->getMessage());
        } catch (Throwable $e) {
            $this->respondWithError(500, ErrorCode::InternalError->value, "Something went wrong. Please try again later.");
        }
    }

    private function handleRequest(Request $request): Response
    {
        $method = $request->method;
        $path = $request->path;

        if (!isset($this->router[$method])) {
            throw new NotFoundException("This route does not exist!");
        }

        if ($path === '') {
            $path = '/';
        }

        foreach ($this->router[$method] as $pattern => $route) {
            $params = $this->matchPath($pattern, $path);
            if ($params !== false) {
                $handler = $route['handler'];
                $middleware = $route['middleware'];

                $finalHandler = fn(Request $req): Response => call_user_func($handler, $req, ...$params);
                if (in_array('auth', $middleware)) {
                    $finalHandler = fn(Request $req): Response => $this->authMiddleware->handle($req, $finalHandler);
                }
                return $finalHandler($request);
            }
        }

        throw new NotFoundException("This route does not exist!");
    }

    private function matchPath(string $pattern, string $path): array|false
    {
        $patternParams = explode('/', $pattern);
        $pathParams = explode('/', $path);

        if (count($patternParams) != count($pathParams)) {
            return false;
        }

        $params = [];
        foreach ($patternParams as $i => $param) {
            if (str_starts_with($param, '{') && str_ends_with($param, '}')) {
                $params[] = $pathParams[$i];
            } elseif ($param != $pathParams[$i]) {
                return false;
            }
        }

        return $params;
    }

    private function respondWithError(int $statusCode, string $errorCode, string $message, array $errors = []): void
    {
        Response::failed($statusCode, $message, $errorCode, $errors)->send();
    }
}
