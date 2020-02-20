<?php

declare(strict_types=1);

namespace App\GitHub\Event;

use Assert\Assert;
use DateTimeImmutable;

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

    public function getMessagePayload(): array
    {
        $payload = $this->payload;
        $repo    = $payload['repository'];
        $release = $payload['release'];
        $author  = $release['author'];
        $name    = $payload['release_name'] ?: sprintf('%s %s', $repo['full_name'], $release['tag_name']);

        return [
            'fallback'    => sprintf(
                '[%s] New release %s created by %s: %s',
                $repo['full_name'],
                $name,
                $author['login'],
                $release['html_url']
            ),
            'color'       => '#4183C4',
            'pretext'     => sprintf(
                '[<%s|%s>] New release <%s|%s> created by <%s|%s>',
                $repo['html_url'],
                $repo['full_name'],
                $release['html_url'],
                $name,
                $author['html_url'],
                $author['login']
            ),
            'author_name' => sprintf('%s (GitHub)', $repo['full_name']),
            'author_link' => $repo['html_url'],
            'author_icon' => self::GITHUB_ICON,
            'title'       => $name,
            'title_link'  => $release['html_url'],
            'text'        => $release['body'],
            'fields'      => [
                [
                    'title' => 'Repository',
                    'value' => sprintf('<%s|%s>', $repo['html_url'], $repo['full_name']),
                    'short' => true,
                ],
                [
                    'title' => 'Released By',
                    'value' => sprintf('<%s|%s>', $author['html_url'], $author['login']),
                    'short' => true,
                ],
            ],
            'footer'      => 'GitHub',
            'footer_icon' => self::GITHUB_ICON,
            'ts'          => (new DateTimeImmutable($release['published_at']))->getTimestamp(),
        ];
    }
}
