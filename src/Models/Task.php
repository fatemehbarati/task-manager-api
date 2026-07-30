<?php
namespace Fatemeh\TaskManagerApi\Models;

use DateTimeImmutable;
use InvalidArgumentException;

class Task{
    private bool $done = false;
    private DateTimeImmutable $created_at;
    private DateTimeImmutable $updated_at;

    public function __construct(private int $id, private string $title)
    {
        $this->created_at = new DateTimeImmutable();
        $this->updated_at = $this->created_at;
    }

    public function getId(): int {
        return $this->id;
    }

    public function getTitle(): string {
        return $this->title;
    }

    public function isDone() : bool {
        return $this->done;
    }

    public function getCreatedAt() : DateTimeImmutable {
        return $this->created_at;
    }

    public function getUpdatedAt() : DateTimeImmutable {
        return $this->updated_at;
    }

    public function markDone() : void {
        if($this->done === false) {
            $this->done = true;
            $this->markUpdateDateTime();
        }
    }

    public function markUndone() : void {
        if($this->done === true) {
            $this->done = false;
            $this->markUpdateDateTime();
        }
    }

    public function changeTitle(string $title) : void {
        if(trim($title) === '') {
            throw new InvalidArgumentException("Title can not be empty!");
        }

        $this->title = $title;
        $this->markUpdateDateTime();
    }

    private function markUpdateDateTime(): void {
        $this->updated_at = new DateTimeImmutable();
    }
}