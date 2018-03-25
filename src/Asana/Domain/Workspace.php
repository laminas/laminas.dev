<?php

declare(strict_types=1);

namespace App\Asana\Domain;

class Workspace
{
    /** @var int|string */
    private $id;

    /** @var string */
    private $name;

    /** @param string[] $data */
    public static function fromState(array $data) : Workspace
    {
        $workspace = new self();

        $workspace->id   = $data['id'];
        $workspace->name = $data['name'];

        return $workspace;
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
