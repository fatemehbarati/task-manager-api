<?php

namespace Fatemeh\TaskManagerApi\Repositories;

use DateTimeImmutable;
use Fatemeh\TaskManagerApi\Exceptions\ApiException;
use Fatemeh\TaskManagerApi\Models\Task;
use PDO;

class TaskRepository implements TaskRepositoryInterface
{
    private PDO $db;

    public function __construct(PDO $dbConnection)
    {
        $this->db = $dbConnection;
    }

    public function getById(int $id): ?Task
    {
        $statement = $this->db->prepare('Select * from tasks where id = :id');
        $statement->execute(['id' => $id]);
        $task = $statement->fetch();
        return $task !== false ? Task::fromArray($task) : null;
    }

    public function getAll(): array
    {
        $tasks = $this->db->query("Select * From tasks")->fetchAll();

        $newTasks = [];
        if (!empty($tasks)) {
            foreach ($tasks as $task) {
                $newTasks[] = Task::fromArray($task);
            }
        }

        return $newTasks;
    }

    public function add(Task $task): Task
    {
        $statement = $this->db->prepare("Insert Into tasks(title, done, created_at, updated_at) 
        Values(:title, :done, :created_at, :updated_at)");

        $statement->execute([
            'title' => $task->getTitle(),
            'done' => (int) $task->isDone(),
            'created_at' => $task->getCreatedAt()->format('Y-m-d H:i:s'),
            'updated_at' => $task->getUpdatedAt()->format('Y-m-d H:i:s')
        ]);

        $id = (int) $this->db->lastInsertId();
        $createdTask = $this->getById($id);
        if (is_null($createdTask)) {
            throw new ApiException("Something went wrong!", 500);
        }

        return $createdTask;
    }

    public function update(Task $task): void
    {
        $statement = $this->db->prepare("Update tasks Set title=:title, done=:done, updated_at=:updated_at Where id=:id");

        $statement->execute(
            [
                'id' => $task->getId(),
                'title' => $task->getTitle(),
                'done' => (int) $task->isDone(),
                'updated_at' => (new DateTimeImmutable())->format('Y-m-d H:i:s')
            ]
        );
    }

    public function delete(int $id): void
    {
        $this->db->prepare("Delete From tasks Where id=:id")->execute(['id' => $id]);
    }
}
