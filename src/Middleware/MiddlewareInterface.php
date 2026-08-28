<?php
namespace Fatemeh\TaskManagerApi\Middleware;

use Fatemeh\TaskManagerApi\Http\Request;
use Fatemeh\TaskManagerApi\Http\Response;

interface MiddlewareInterface {
    public function handle(Request $request, callable $next) : Response;
}