<?php
require __DIR__ . '/../vendor/autoload.php';

use Fatemeh\TaskManagerApi\Cache\CachedTaskRepository;
use Fatemeh\TaskManagerApi\Cache\RedisCache;
use Fatemeh\TaskManagerApi\Database\Connection;
use Fatemeh\TaskManagerApi\Exceptions\NotFoundException;
use Fatemeh\TaskManagerApi\Exceptions\ValidationException;
use Fatemeh\TaskManagerApi\Models\Task;
use Fatemeh\TaskManagerApi\Repositories\TaskRepository;
use Fatemeh\TaskManagerApi\Router;
use Fatemeh\TaskManagerApi\Services\TaskValidator;

$taskValidator = new TaskValidator();
$dbConnection = (new Connection())->getConnection();
$taskRepository = new TaskRepository($dbConnection);
$cache = new RedisCache();
$cachedTaskRepository = new CachedTaskRepository($taskRepository, $cache);

$router = new Router();
$router->get('/tasks', function () use ($cachedTaskRepository): void {
    $tasks = $cachedTaskRepository->getAll();
    echo json_encode(['tasks' => $tasks]);
});
$router->get('/tasks/{id}', function (int $id) use ($cachedTaskRepository) {
    $task = $cachedTaskRepository->getById($id);
    if (is_null($task)) {
        throw new NotFoundException("Task $id not found");
    }
    echo json_encode(['message' => "Task $id is listed", 'task' => $task]);
});
$router->post('/tasks', function () use ($taskValidator, $cachedTaskRepository): void {
    $body = json_decode(file_get_contents('php://input'), true);
    $errors = $taskValidator->validateInput($body);
    if (!empty($errors)) {
        throw new ValidationException($errors);
    }

    $task = new Task(0, $body['title']); // 0 is a placeholder — real id is assigned by the DB
    if (array_key_exists('done', $body) && $body['done'] === true) {
        $task->markDone();
    }

    $createdTask = $cachedTaskRepository->add($task);
    echo json_encode(['message' => "Task is added", 'Created Task' => $createdTask]);
});
$router->put('/tasks/{id}', function (int $id) use ($taskValidator, $cachedTaskRepository): void {
    $body = json_decode(file_get_contents('php://input'), true);
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
    echo json_encode(['message' => "Task $id is updated", 'task' => $task]);
});
$router->delete('/tasks/{id}', function (int $id) use ($cachedTaskRepository): void {
    $task = $cachedTaskRepository->getById($id);
    if (is_null($task)) {
        throw new NotFoundException("Task $id not found");
    }

    $cachedTaskRepository->delete($id);
    echo json_encode(['message' => "Task $id is deleted"]);
});

$router->dispatch();
