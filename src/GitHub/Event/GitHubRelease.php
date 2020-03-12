<?php

declare(strict_types=1);

namespace App\GitHub\Event;

use App\Slack\Domain\TextObject;
use Assert\Assert;

use function in_array;
use function sprintf;

/**
 * @see https://developer.github.com/v3/repos/releases/#get-a-single-release
 */
final class GitHubRelease extends AbstractGitHubEvent
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
        Assert::that($this->payload['release'])->keyExists('body');
        Assert::that($this->payload['release'])->keyExists('draft');
        Assert::that($this->payload['release'])->keyIsset('published_at');
        Assert::that($this->payload['release']['author'])->isArray();
        Assert::that($this->payload['release']['author'])->keyIsset('login');
        Assert::that($this->payload['release']['author'])->keyIsset('html_url');
        Assert::that($this->payload['repository'])->isArray();
        Assert::that($this->payload['repository'])->keyIsset('full_name');
    }

    public function ignore(): bool
    {
        if (! in_array($this->payload['action'], ['published'], true)) {
            return true;
        }
        return ! $this->isPublished();
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
        return $this->payload['release']['body'] ?? '';
    }

    public function getPublicationDate(): string
    {
        return $this->payload['release']['published_at'];
    }

    public function getPackage(): string
    {
        return $this->payload['repository']['full_name'];
    }

    public function getReleaseName(): string
    {
        $payload = $this->payload;
        $repo    = $payload['repository'];
        $release = $payload['release'];
        return $payload['release_name'] ?? sprintf('%s %s', $repo['full_name'], $release['tag_name']);
    }

    public function getAuthorName(): string
    {
        return $this->payload['release']['author']['login'];
    }

    public function getAuthorUrl(): string
    {
        return $this->payload['release']['author']['html_url'];
    }

    public function getFallbackMessage(): string
    {
        $payload = $this->payload;
        $repo    = $payload['repository'];
        $release = $payload['release'];
        $author  = $release['author'];
        
        return sprintf(
            '[%s] New release %s created by %s: %s',
            $repo['full_name'],
            $this->getReleaseName(),
            $author['login'],
            $release['html_url']
        );
    }

    public function getMessageBlocks(): array
    {
        $payload = $this->payload;
        $repo    = $payload['repository'];
        $release = $payload['release'];
        $author  = $release['author'];
        $name    = $payload['release_name'] ?? sprintf('%s %s', $repo['full_name'], $release['tag_name']);

        return [
            $this->createContextBlock($repo['html_url'], sprintf(
                '[<%s|%s>] New release <%s|%s> created by <%s|%s>',
                $repo['html_url'],
                $repo['full_name'],
                $release['html_url'],
                $name,
                $author['html_url'],
                $author['login']
            )),
            [
                'type' => 'section',
                'text' => [
                    'type' => TextObject::TYPE_MARKDOWN,
                    'text' => $release['body'],
                ],
            ],
            $this->createFieldsBlock($repo, $author),
        ];
    }

    private function createFieldsBlock(array $repo, array $author): array
    {
        return [
            'type'   => 'section',
            'fields' => [
                [
                    'type' => TextObject::TYPE_MARKDOWN,
                    'text' => sprintf("*Repository*\n<%s|%s>", $repo['html_url'], $repo['full_name']),
                ],
                [
                    'type' => TextObject::TYPE_MARKDOWN,
                    'text' => sprintf("*Released By*\n<%s|%s>", $author['html_url'], $author['login']),
                ],
            ],
        ];
    }
}
