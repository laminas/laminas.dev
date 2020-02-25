<?php

declare(strict_types=1);

namespace App\GitHub\Event;

class DocsBuildAction
{
    /** @var string */
    private $repo;

    public function __construct(string $repo)
    {
        $this->repo = $repo;
    }

    public function repo(): string
    {
        return $this->repo;
    }
}
