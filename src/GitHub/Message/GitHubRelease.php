<?php

declare(strict_types=1);

namespace App\GitHub\Message;

use Assert\Assert;

use function in_array;

/**
 * @see https://developer.github.com/v3/repos/releases/#get-a-single-release
 */
class GitHubRelease implements GitHubMessageInterface
{
    /** @var array */
    private $payload;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    public function validate(): void
    {
        Assert::that($this->payload['action'])->notEmpty()->string();
        Assert::that($this->payload['release'])->isArray();
        Assert::that($this->payload['release'])->keyIsset('html_url');
        Assert::that($this->payload['release'])->keyIsset('tag_name');
        Assert::that($this->payload['release'])->keyIsset('body');
        Assert::that($this->payload['release'])->keyIsset('draft');
        Assert::that($this->payload['release'])->keyIsset('published_at');
        Assert::that($this->payload['release']['author'])->isArray();
        Assert::that($this->payload['release']['author'])->keyIsset('login');
        Assert::that($this->payload['release']['author'])->keyIsset('html_url');
        Assert::that($this->payload['repository'])->isArray();
        Assert::that($this->payload['repository'])->keyIsset('full_name');
    }

    public function ignore(): bool
    {
        return ! in_array($this->payload['action'], [
            'published',
        ], true);
    }

    public function getAction(): string
    {
        return $this->payload['action'];
    }

    public function isPublished(): bool
    {
        return $this->payload['release']['draft'] === false;
    }

    public function getUrl(): string
    {
        return $this->payload['release']['html_url'];
    }

    public function getVersion(): string
    {
        return $this->payload['release']['tag_name'];
    }

    public function getChangelog(): string
    {
        return $this->payload['release']['body'];
    }

    public function getPublicationDate(): string
    {
        return $this->payload['release']['published_at'];
    }

    public function getPackage(): string
    {
        return $this->payload['repository']['full_name'];
    }

    public function getAuthorName(): string
    {
        return $this->payload['release']['author']['login'];
    }

    public function getAuthorUrl(): string
    {
        return $this->payload['release']['author']['html_url'];
    }
}
