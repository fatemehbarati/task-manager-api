<?php

namespace Fatemeh\TaskManagerApi\Cache;

use Fatemeh\TaskManagerApi\Models\Task;
use Fatemeh\TaskManagerApi\Repositories\TaskRepositoryInterface;

class CachedTaskRepository implements TaskRepositoryInterface
{
    public function __construct(
        private TaskRepositoryInterface $taskRepositoryInterface,
        private CacheInterface $cacheInterface
    ) {}

    public function getById(int $id): ?Task
    {
        $key = "task:$id";
        $cached = $this->cacheInterface->get($key);

        if ($cached !== null) {
            return Task::fromArray(json_decode($cached, true));
        }

        $realData = $this->taskRepositoryInterface->getById($id);
        if (is_null($realData)) {
            return null;
        }

        $this->cacheInterface->set($key, json_encode($realData), 10);
        return $realData;
    }

    public function getAll(): array
    {
        $tasks = $this->taskRepositoryInterface->getAll();

        return $tasks;
    }

    public function add(Task $task): Task
    {
        return $this->taskRepositoryInterface->add($task);
    }

    public function update(Task $task): void
    {
        $this->taskRepositoryInterface->update($task);
        $this->cacheInterface->delete("task:{$task->getId()}");
    }

    public function delete(int $id): void
    {
        $this->taskRepositoryInterface->delete($id);
        $this->cacheInterface->delete("task:{$id}");
    }
}
