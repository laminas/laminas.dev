<?php

declare(strict_types=1);

namespace App\Slack\Event;

class Tweet
{
    /** @var null|string */
    private $media;

    /** @var string */
    private $message;

    /** @var string */
    private $responseUrl;

    public function __construct(string $message, ?string $media, string $responseUrl)
    {
        $this->message     = $message;
        $this->media       = $media;
        $this->responseUrl = $responseUrl;
    }

    public function message(): string
    {
        return $this->message;
    }

    public function media(): ?string
    {
        return $this->media;
    }

    public function responseUrl(): string
    {
        return $this->responseUrl;
    }
}
