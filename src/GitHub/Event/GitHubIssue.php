<?php

declare(strict_types=1);

namespace App\GitHub\Event;

use App\Slack\Domain\TextObject;
use Assert\Assert;

use function in_array;
use function sprintf;

/**
 * @see https://developer.github.com/v3/activity/events/types/#issuesevent
 */
final class GitHubIssue extends AbstractGitHubEvent
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
        Assert::that($this->payload['issue'])->isArray();
        Assert::that($this->payload['issue'])->keyIsset('html_url');
        Assert::that($this->payload['issue'])->keyIsset('number');
        Assert::that($this->payload['issue'])->keyIsset('title');
        Assert::that($this->payload['issue'])->keyExists('body');
        Assert::that($this->payload['repository'])->isArray();
        Assert::that($this->payload['repository'])->keyIsset('full_name');
        Assert::that($this->payload['repository'])->keyIsset('html_url');
        Assert::that($this->payload['sender'])->isArray();
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

    public function getFallbackMessage(): string
    {
        $payload = $this->payload;
        $issue   = $payload['issue'];
        $author  = $payload['sender'];
        $repo    = $payload['repository'];

        return sprintf(
            '[%s] Issue #%s %s by %s: %s',
            $repo['full_name'],
            $issue['number'],
            $payload['action'],
            $author['login'],
            $issue['html_url']
        );
    }

    public function getMessageBlocks(): array
    {
        $payload = $this->payload;
        $issue   = $payload['issue'];
        $author  = $payload['sender'];
        $repo    = $payload['repository'];

        return [
            $this->createContextBlock($repo['html_url'], sprintf(
                '<%s|*[%s] Issue %s#%s %s*>',
                $issue['html_url'],
                $payload['action'],
                $repo['full_name'],
                $issue['number'],
                $issue['title']
            )),
            [
                'type' => 'section',
                'text' => [
                    'type' => TextObject::TYPE_MARKDOWN,
                    'text' => $issue['body'],
                ],
            ],
            $this->createFieldsBlock($payload['action'], $repo, $author),
        ];
    }

    private function createFieldsBlock(string $action, array $repo, array $author): array
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
                    'text' => sprintf("*Reporter*\n<%s|%s>", $author['html_url'], $author['login']),
                ],
                [
                    'type' => TextObject::TYPE_MARKDOWN,
                    'text' => sprintf("*Status*\n%s", $action),
                ],
            ],
        ];
    }
}
