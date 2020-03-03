<?php

declare(strict_types=1);

namespace App\GitHub\Listener;

use Assert\Assert;

class PullRequest
{
    /** @var array */
    private $payload;

    /** @var array */
    private $pullRequest;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    public function validate(): void
    {
        if (array_key_exists('incomplete_results', $this->payload)) {
            Assert::that($this->payload['incomplete_results'])->false();
        }
        Assert::that($this->payload['items'])->isArray()->notEmpty();
    }

    public function getNumber(): int
    {
        return $this->getPullRequest()['number'];
    }

    public function getTitle(): string
    {
        return $this->getPullRequest()['title'];
    }

    public function getUrl(): string
    {
        return $this->getPullRequest()['html_url'];
    }

    private function getPullRequest(): array
    {
        $items = $this->payload['items'];
        return array_shift($items);
    }
}
