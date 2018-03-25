<?php

declare(strict_types=1);

namespace App\Asana\Domain;

class Project
{
    /** @var int|string */
    private $id;

    /** @var string */
    private $name;

    /** @param string[] $data */
    public static function fromState(array $data) : Project
    {
        $project = new self();

        $project->id   = $data['id'];
        $project->name = $data['name'];

        return $project;
    }

    public function id() : string
    {
        return (string) $this->id;
    }

    public function name() : string
    {
        return $this->name;
    }
}
