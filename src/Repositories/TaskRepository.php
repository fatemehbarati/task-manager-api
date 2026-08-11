<?php
namespace Fatemeh\TaskManagerApi\Repositories;

use Fatemeh\TaskManagerApi\Models\Task;
use Override;
use PDO;

class TaskRepository implements TaskRepositoryInterface {
    private PDO $db;

    public function __construct(PDO $dbConnection)
    {
        $this->db = $dbConnection;
    }

    #[Override]
    public function getById(int $id): ?Task {
        $statement = $this->db->prepare('Select * from tasks where id = :id');
        $statement->execute(['id' => $id]);
        return $statement->fetch() ?: null;
    }
}