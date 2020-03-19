<?php

declare(strict_types=1);

namespace App\Slack\Event;

class TwitterReply
{
    /** @var string */
    private $message;

    /** @var string */
    private $replyUrl;

    /** @var string */
    private $responseUrl;

    public function __construct(
        string $replyUrl,
        string $message,
        string $responseUrl
    ) {
        $this->replyUrl    = $replyUrl;
        $this->message     = $message;
        $this->responseUrl = $responseUrl;
    }

    public function message(): string
    {
        return $this->message;
    }

    public function replyUrl(): string
    {
        return $this->replyUrl;
    }

    public function responseUrl(): string
    {
        return $this->responseUrl;
    }
}
