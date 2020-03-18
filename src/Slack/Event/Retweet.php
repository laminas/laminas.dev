<?php

declare(strict_types=1);

namespace App\Slack\Event;

class Retweet
{
    /** @var string */
    private $original;

    /** @var string */
    private $responseUrl;

    public function __construct(string $original, string $responseUrl)
    {
        $this->original    = $original;
        $this->responseUrl = $responseUrl;
    }

    public function original(): string
    {
        return $this->original;
    }

    public function responseUrl(): string
    {
        return $this->responseUrl;
    }
}
