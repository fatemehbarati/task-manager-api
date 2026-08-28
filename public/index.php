<?php
require __DIR__ . '/../vendor/autoload.php';

use Fatemeh\TaskManagerApi\Cache\CachedTaskRepository;
use Fatemeh\TaskManagerApi\Cache\RedisCache;
use Fatemeh\TaskManagerApi\Database\Connection;
use Fatemeh\TaskManagerApi\Exceptions\NotFoundException;
use Fatemeh\TaskManagerApi\Exceptions\ValidationException;
use Fatemeh\TaskManagerApi\Http\Request;
use Fatemeh\TaskManagerApi\Http\Response;
use Fatemeh\TaskManagerApi\Logging\MonologAdapter;
use Fatemeh\TaskManagerApi\Middleware\AuthMiddleware;
use Fatemeh\TaskManagerApi\Middleware\LoggingMiddleware;
use Fatemeh\TaskManagerApi\Models\Task;
use Fatemeh\TaskManagerApi\Repositories\TaskRepository;
use Fatemeh\TaskManagerApi\Router;
use Fatemeh\TaskManagerApi\Services\TaskValidator;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;

$taskValidator = new TaskValidator();
$dbConnection = (new Connection())->getConnection();
$taskRepository = new TaskRepository($dbConnection);
$cache = new RedisCache();
$cachedTaskRepository = new CachedTaskRepository($taskRepository, $cache);

$apiLogger = new Logger('api');
$streamHandler = new StreamHandler(__DIR__ . '/../storage/logs/app.log', Level::Debug);
$apiLogger->pushHandler($streamHandler);
$logger = new MonologAdapter($apiLogger);
$logger->log('Info', "Application started");

$loggingMiddleware = new LoggingMiddleware($logger);
$authMiddleware = new AuthMiddleware($logger);

$router = new Router($loggingMiddleware, $authMiddleware);
$router->get('/tasks', function (Request $request) use ($cachedTaskRepository): Response {
    $tasks = $cachedTaskRepository->getAll();
    return new Response(200, ['tasks' => $tasks]);
});

$router->get('/tasks/{id}', function (Request $request, int $id) use ($cachedTaskRepository, $logger): Response {
    $task = $cachedTaskRepository->getById($id);
    if (is_null($task)) {
        $logger->log('error', "Task not found.", ['id' => $id]);
        throw new NotFoundException("Task $id not found");
    }

    return new Response(200, ['message' => "Task $id is listed", 'task' => $task]);
});

$router->post('/tasks', function (Request $request) use ($taskValidator, $cachedTaskRepository): Response {
    $body = $request->body;
    $errors = $taskValidator->validateInput($body);
    if (!empty($errors)) {
        throw new ValidationException($errors);
    }

    $task = new Task(0, $body['title']); // 0 is a placeholder — real id is assigned by the DB
    if (array_key_exists('done', $body) && $body['done'] === true) {
        $task->markDone();
    }

    $createdTask = $cachedTaskRepository->add($task);
    return new Response(200, ['message' => "Task is added", 'Created Task' => $createdTask]);
}, ['auth']);
$router->put('/tasks/{id}', function (Request $request, int $id) use ($taskValidator, $cachedTaskRepository): Response {
    $body = $request->body;
    $errors = $taskValidator->validateInput($body);
    if (!empty($errors)) {
        throw new ValidationException($errors);
    }

    $task = $cachedTaskRepository->getById($id);
    if (is_null($task)) {
        throw new NotFoundException("Task $id not found");
    }

    $task->changeTitle($body['title']);
    if (array_key_exists('done', $body)) {
        $body['done'] ? $task->markDone() : $task->markUndone();
    }

    $cachedTaskRepository->update($task);
    return new Response(200, ['message' => "Task $id is updated", 'task' => $task]);
}, ['auth']);
$router->delete('/tasks/{id}', function (Request $request, int $id) use ($cachedTaskRepository): Response {
    $task = $cachedTaskRepository->getById($id);
    if (is_null($task)) {
        throw new NotFoundException("Task $id not found");
    }

    $cachedTaskRepository->delete($id);
    return new Response(200, ['message' => "Task $id is deleted"]);
}, ['auth']);

$router->dispatch();
