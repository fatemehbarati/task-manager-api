<?php
namespace Fatemeh\TaskManagerApi;

use Fatemeh\TaskManagerApi\Exceptions\ApiException;
use Fatemeh\TaskManagerApi\Exceptions\InvalidException;
use Fatemeh\TaskManagerApi\Exceptions\NotFoundException;
use Fatemeh\TaskManagerApi\Exceptions\ValidationException;
use Throwable;

class Router {
    private array $router = [];

    public function get(string $path, callable $handler) : void {
        $this->addRouter('GET', $path, $handler);
    }

    public function post(string $path, callable $handler) : void {
        $this->addRouter('POST', $path, $handler);
    }

    public function put(string $path, callable $handler) : void {
        $this->addRouter('PUT', $path, $handler);
    }

    public function delete(string $path, callable $handler) : void {
        $this->addRouter('DELETE', $path, $handler);
    }

    private function addRouter(string $method, string $path, callable $handler): void {
        $this->router[$method][$path] = $handler;
    }

    public function dispatch() : void {
        header('Content-Type: application/json');
        try{
            $this->handleRequest();
        } catch (ValidationException $e) {
            $this->respondWithError($e->getStatusCode(), $e->getMessage(), $e->getErrors());
        } catch (NotFoundException|InvalidException $e) {
            $this->respondWithError($e->getStatusCode(), $e->getMessage());
        } catch (ApiException $e) {
            $this->respondWithError($e->getStatusCode(), $e->getMessage());
        } catch (Throwable $e) {
            $this->respondWithError(500, "Something went wrong. Please try again later.");
        }
    }

    private function handleRequest() : void {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

        if(!isset($this->router[$method])) {
            throw new NotFoundException("This route does not exist!");
        }

        if($path === '') {
            $path = '/';
        }

        foreach($this->router[$method] as $pattern => $handler) {
            $params = $this->matchPath($pattern, $path);
            if($params !== false) {
                call_user_func($handler, ...$params);
                return;
            }
        }
        
        throw new NotFoundException("This route does not exist!");
    }

    private function matchPath(string $pattern, string $path) : array|false {
        $patternParams = explode('/', $pattern);
        $pathParams = explode('/', $path);

        if(count($patternParams) != count($pathParams)) {
            return false;
        }

        $params = [];
        foreach($patternParams as $i => $param) {
            if(str_starts_with($param, '{') && str_ends_with($param, '}')) {
                $params[] = $pathParams[$i];
            } elseif($param != $pathParams[$i]) {
                return false;
            }
        }
        
        return $params;
    }

    private function respondWithError(int $statusCode, string $message, array $errors = []) : void {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        if(!empty($errors)) {
            echo json_encode(['errors' => $errors]);
        } else {
            echo json_encode(['error' => $message]);
        }
    }
}