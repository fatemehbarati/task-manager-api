<?php

namespace Fatemeh\TaskManagerApi\Database;

use PDO;
use PDOException;

final class Connection
{
    private static ?self $instance = null;
    private ?PDO $pdo = null;

    private function __construct() {}

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function getConnection(): PDO
    {
        try {
            if ($this->pdo === null) {
                $this->pdo = new PDO(
                    'mysql:host=127.0.0.1;dbname=task_manager;charset=utf8mb4',
                    $_ENV['DATABASE_USERNAME'],
                    $_ENV['DATABASE_PASSWORD'],
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    ]
                );
            }
            return $this->pdo;
        } catch (PDOException $e) {
            throw new PDOException("Connection Failed: " . $e->getMessage(), (int) $e->getCode(), $e);
        }
    }
}
