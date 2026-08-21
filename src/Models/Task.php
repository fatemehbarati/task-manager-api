<?php
namespace Fatemeh\TaskManagerApi\Models;

use DateTimeImmutable;
use InvalidArgumentException;
use JsonSerializable;

class Task implements JsonSerializable{
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

    public function jsonSerialize(): array
    {
        return [
            "id" => $this->id,
            "title" => $this->title,
            "done" => $this->done,
            "created_at" => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }

    public static function fromArray(array $data) : Task {
        $task = new Task($data['id'], $data['title']);
        $task->done = $data['done'];
        $task->created_at = new DateTimeImmutable($data['created_at']);
        $task->updated_at = new DateTimeImmutable($data['updated_at']);

        return $task;
    }

}