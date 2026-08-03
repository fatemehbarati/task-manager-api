<?php
namespace Fatemeh\TaskManagerApi\Services;

use Fatemeh\TaskManagerApi\Models\Task;

class TaskService {
    public function filterByStatus(array $tasks, bool $status) : array {
        $filteredTasks = array_filter($tasks, function (Task $task) use($status) {
            return $task->isDone() === $status;
            });

        return array_values($filteredTasks);
    }

    public function searchByTitle(array $tasks, string $title) : array {
        $filteredTasks = array_filter($tasks, function (Task $task) use($title) {
            return stripos($task->getTitle(), trim($title)) !== false;
        });

        return array_values($filteredTasks);
    }
}