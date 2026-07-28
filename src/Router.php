<?php
namespace Fatemeh\TaskManagerApi;

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
        $method = $_SERVER['REQUEST_METHOD'];
        $path = rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

        if(!isset($this->router[$method])) {
            $this->notFound();
            return;
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

        $this->notFound();
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

    private function notFound() : void {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'This route does not exist!']);
    }
}