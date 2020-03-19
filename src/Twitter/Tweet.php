<?php

declare(strict_types=1);

namespace App\Twitter;

use DateTimeInterface;

class Tweet
{
    /** @var string */
    private $message;

    /** @var DateTimeInterface */
    private $timestamp;

    /** @var string */
    private $url;

    public function __construct(
        string $message,
        string $url,
        DateTimeInterface $timestamp
    ) {
        $this->message   = $message;
        $this->url       = $url;
        $this->timestamp = $timestamp;
    }

    public function message(): string
    {
        return $this->message;
    }

    public function timestamp(): DateTimeInterface
    {
        return $this->timestamp;
    }

    public function url(): string
    {
        return $this->url;
    }
}
