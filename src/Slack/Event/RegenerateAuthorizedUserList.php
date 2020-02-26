<?php

declare(strict_types=1);

namespace App\Slack\Event;

class RegenerateAuthorizedUserList
{
    /** @var string */
    private $responseUrl;

    public function __construct(string $responseUrl)
    {
        $this->responseUrl = $responseUrl;
    }

    public function responseUrl(): string
    {
        return $this->responseUrl;
    }
}
