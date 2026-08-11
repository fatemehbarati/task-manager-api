<?php
namespace Fatemeh\TaskManagerApi\Database;

use PDO;
use PDOException;

class Connection {
    private ?PDO $dbInstance = null;
    private string $username = 'root';
    private string $password = 'root';

    public function __construct()
    {

    }

    public function getConnection() : PDO {
        try {
            if(!$this->dbInstance) {
                $this->dbInstance = new PDO('mysql:host=127.0.0.1;dbname=task_manager;charset=utf8mb4', 
                                            $this->username, 
                                            $this->password,
                                            [
                                                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                                                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                                            ]);
            }

            return $this->dbInstance;
        } catch (PDOException $e) {
            throw new PDOException("Connection Failed: " . $e->getMessage(), $e->getCode(), $e);
        }
    }
}