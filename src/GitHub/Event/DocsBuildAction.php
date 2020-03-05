<?php

declare(strict_types=1);

namespace App\GitHub\Event;

class DocsBuildAction
{
    /** @var string */
    private $repo;

    /** @var string */
    private $responseUrl;

    public function __construct(string $repo, string $responseUrl)
    {
        $this->repo        = $repo;
        $this->responseUrl = $responseUrl;
    }

    public function repo(): string
    {
        return $this->repo;
    }

    public function responseUrl(): string
    {
        return $this->responseUrl;
    }
}
