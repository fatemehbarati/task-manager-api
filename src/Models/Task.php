<?php
namespace Fatemeh\TaskManagerApi\Models;

class Task{
    public function __construct(public string $title, public bool $done = false)
    {
    }
}