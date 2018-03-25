<?php

declare(strict_types=1);

namespace App\Asana\Domain;

use const FILTER_VALIDATE_BOOLEAN;
use function filter_var;

class Task
{
    /** @var null|int|string */
    private $id;

    /** @var string */
    private $name;

    /** @var string */
    private $notes;

    /** @var bool|string */
    private $completed;

    /** @param string[] $data */
    public static function fromState(array $data) : Task
    {
        $task = new self();

        $task->id        = $data['id'] ?? null;
        $task->name      = $data['name'];
        $task->notes     = $data['notes'] ?? null;
        $task->completed = $data['completed'] ?? false;

        return $task;
    }

    public function update(string $name, string $notes, bool $completed) : void
    {
        $this->name      = $name;
        $this->notes     = $notes;
        $this->completed = $completed;
    }

    public function close() : void
    {
        $this->completed = true;
    }

    public function open() : void
    {
        $this->completed = false;
    }

    public function id() : ?string
    {
        if ($this->id === null) {
            return null;
        }

        return (string) $this->id;
    }

    public function name() : string
    {
        return $this->name;
    }

    public function notes() : string
    {
        return $this->notes;
    }

    public function completed() : bool
    {
        return filter_var($this->completed, FILTER_VALIDATE_BOOLEAN);
    }
}
