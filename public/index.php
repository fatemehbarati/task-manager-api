<?php
require __DIR__ . '/../vendor/autoload.php';

use Fatemeh\TaskManagerApi\Models\Task;

$task = new Task("First task");
echo $task->title;