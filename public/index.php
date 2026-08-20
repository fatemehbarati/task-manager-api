<?php
require __DIR__ . '/../vendor/autoload.php';

use Fatemeh\TaskManagerApi\Exceptions\ValidationException;
use Fatemeh\TaskManagerApi\Router;
use Fatemeh\TaskManagerApi\Services\TaskValidator;

$taskValidator = new TaskValidator();

$router = new Router();
$router->get('/tasks', function () : void {
    echo json_encode(['message' => 'All tasks are listed']);
});
$router->get('/tasks/{id}', function ($id) {
    echo json_encode(['message' => "Task $id is listed"]);
});
$router->post('/tasks', function () use ($taskValidator) : void {
    $body = json_decode(file_get_contents('php://input'), true);
    $errors = $taskValidator->validateInput($body);
    if (!empty($errors)) {
        throw new ValidationException($errors);
    }
    
    echo json_encode(['message' => "Task is added"]);
});
$router->put('/tasks/{id}', function (int $id) use($taskValidator) : void {
    $body = json_decode(file_get_contents('php://input'), true);
    $errors = $taskValidator->validateInput($body);
    if (!empty($errors)) {
        throw new ValidationException($errors);
    }
    echo json_encode(['message' => "Task $id is updated", 'data' => $body]);
});
$router->delete('/tasks/{id}', function (int $id) : void {
    $body = json_decode(file_get_contents('php://input'), true);
    echo json_encode(['message' => "Task $id is deleted", 'data' => $body]);
});

$router->dispatch();