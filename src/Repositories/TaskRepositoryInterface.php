<?php
namespace Fatemeh\TaskManagerApi\Repositories;

use Fatemeh\TaskManagerApi\Models\Task;

interface TaskRepositoryInterface {
    // /**
    //  * @return Task[]
    //  */
    // public function getAll() : array;

    /**
     * @param int $id
     * @return ?Task
     */
    public function getById(int $id) : ?Task;

    // /**
    //  * @param Task $task
    //  * @return Task
    //  */
    // public function add(Task $task) : Task;

    // /**
    //  * @param Task $task
    //  */
    // public function update(Task $task) : void;

    // /**
    //  * @param int $id
    //  */
    // public function delete(int $id) : void;
}