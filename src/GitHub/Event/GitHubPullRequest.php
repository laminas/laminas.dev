<?php

declare(strict_types=1);

namespace App\GitHub\Event;

use App\Slack\Domain\TextObject;
use Assert\Assert;

use function in_array;
use function sprintf;

/**
 * @see https://developer.github.com/v3/activity/events/types/#pullrequestevent
 */
final class GitHubPullRequest extends AbstractGitHubEvent
{
    /** @var array */
    private $payload;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    public function validate(): void
    {
        Assert::that($this->payload)->keyIsset('action');
        Assert::that($this->payload)->keyIsset('repository');
        Assert::that($this->payload['repository'])->isArray();
        Assert::that($this->payload['repository'])->keyIsset('full_name');
        Assert::that($this->payload['repository'])->keyIsset('html_url');
        Assert::that($this->payload)->keyIsset('pull_request');
        Assert::that($this->payload['pull_request'])->keyIsset('html_url');
        Assert::that($this->payload['pull_request'])->keyIsset('number');
        Assert::that($this->payload['pull_request'])->keyIsset('title');
        Assert::that($this->payload)->keyIsset('sender');
        Assert::that($this->payload['sender'])->keyIsset('login');
        Assert::that($this->payload['sender'])->keyIsset('html_url');
    }

    public function ignore(): bool
    {
        return ! in_array($this->payload['action'], [
            'opened',
            'closed',
            'reopened',
        ], true);
    }

    public function getAction(): string
    {
        $payload = $this->payload;
        $pr      = $payload['pull_request'];
        return $payload['action'] === 'closed' && isset($pr['merged'])
            ? 'merged'
            : $payload['action'];
    }

    public function getFallbackMessage(): string
    {
        $payload = $this->payload;
        $pr      = $payload['pull_request'];
        $author  = $payload['sender'];
        $repo    = $payload['repository'];
        return sprintf(
            '[%s] Pull request %s by %s: %s',
            $repo['full_name'],
            $this->getAction(),
            $author['login'],
            $pr['html_url']
        );
    }

    public function getMessageBlocks(): array
    {
        $payload = $this->payload;
        $pr      = $payload['pull_request'];
        $author  = $payload['sender'];
        $repo    = $payload['repository'];
        $action  = $this->getAction();

        $blocks = [
            $this->createContextBlock($repo['html_url']),
            [
                'type' => 'section',
                'text' => [
                    'type' => TextObject::TYPE_MARKDOWN,
                    'text' => sprintf(
                        '<%s|*[%s] Pull request #%s %s*>',
                        $pr['html_url'],
                        $action,
                        $repo['full_name'],
                        $pr['number'],
                        $pr['title']
                    ),
                ],
            ],
        ];

        if ($action === 'opened') {
            $blocks[] = [
                'type' => 'section',
                'text' => [
                    'type' => TextObject::TYPE_MARKDOWN,
                    'text' => $pr['body'],
                ],
            ];
        }

        $blocks[] = $this->createFieldsBlock($repo, $author);
        return $blocks;
    }

    private function createFieldsBlock(array $repo, array $author): array
    {
        return [
            'type'   => 'section',
            'fields' => [
                [
                    'type' => TextObject::TYPE_MARKDOWN,
                    'text' => '*Repository*',
                ],
                [
                    'type' => TextObject::TYPE_MARKDOWN,
                    'text' => '*Reporter*',
                ],
                [
                    'type' => TextObject::TYPE_MARKDOWN,
                    'text' => '*Status*',
                ],
                [
                    'type' => TextObject::TYPE_MARKDOWN,
                    'text' => sprintf('<%s>', $repo['html_url']),
                ],
                [
                    'type' => TextObject::TYPE_MARKDOWN,
                    'text' => sprintf('<%s|%s>', $author['html_url'], $author['login']),
                ],
                [
                    'type' => TextObject::TYPE_MARKDOWN,
                    'text' => $this->getAction(),
                ],
            ],
        ];
    }
}
