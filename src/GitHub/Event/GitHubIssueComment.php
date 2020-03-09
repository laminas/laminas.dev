<?php

declare(strict_types=1);

namespace App\GitHub\Event;

use App\Slack\Domain\TextObject;
use Assert\Assert;

use function array_merge;
use function in_array;
use function sprintf;
use function ucfirst;

/**
 * @see https://developer.github.com/v3/activity/events/types/#issuecommentevent
 */
class GitHubIssueComment extends AbstractGitHubEvent
{
    private const TYPE_ISSUE        = 'issue';
    private const TYPE_PULL_REQUEST = 'pull request';

    /** @var array */
    private $payload;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    public function validate(): void
    {
        Assert::that($this->payload['action'])->notEmpty()->string();
        Assert::that($this->payload['comment'])->isArray();
        Assert::that($this->payload['comment'])->keyExists('body');
        Assert::that($this->payload['comment'])->keyIsset('created_at');
        Assert::that($this->payload['comment'])->keyIsset('html_url');
        Assert::that($this->payload['comment'])->keyIsset('user');
        Assert::that($this->payload['comment']['user'])->isArray();
        Assert::that($this->payload['comment']['user'])->keyIsset('login');
        Assert::that($this->payload['comment']['user'])->keyIsset('html_url');
        Assert::that($this->payload['issue'])->isArray();
        Assert::that($this->payload['issue'])->keyIsset('html_url');
        Assert::that($this->payload['issue'])->keyIsset('number');
        Assert::that($this->payload['issue'])->keyExists('title');
        Assert::that($this->payload['repository'])->isArray();
        Assert::that($this->payload['repository'])->keyIsset('full_name');
        Assert::that($this->payload['repository'])->keyIsset('html_url');
    }

    public function ignore(): bool
    {
        return ! in_array($this->payload['action'], [
            'created',
        ], true);
    }

    public function getIssueType(): string
    {
        $payload = $this->payload;
        $issue   = $payload['issue'];

        return isset($issue['pull_request']) ? self::TYPE_PULL_REQUEST : self::TYPE_ISSUE;
    }

    public function getFallbackMessage(): string
    {
        $payload = $this->payload;
        $comment = $payload['comment'];
        $author  = $comment['user'];
        $issue   = $payload['issue'];
        $repo    = $payload['repository'];
        
        return sprintf(
            '[%s] New comment by %s on %s #%s %s: %s',
            $repo['full_name'],
            $author['login'],
            $this->getIssueType(),
            $issue['number'],
            $issue['title'],
            $comment['html_url']
        );
    }

    public function getMessageBlocks(): array
    {
        $payload = $this->payload;
        $comment = $payload['comment'];
        $author  = $comment['user'];
        $issue   = $payload['issue'];
        $repo    = $payload['repository'];

        $issueType = isset($issue['pull_request']) ? self::TYPE_PULL_REQUEST : self::TYPE_ISSUE;
        $issueUrl  = $issueType === self::TYPE_PULL_REQUEST
            ? $issue['pull_request']['html_url']
            : $issue['html_url'];

        return array_merge([
            $this->createContextBlock($repo['html_url'], sprintf(
                '<%s|*New comment on %s#%s %s*>',
                $comment['html_url'],
                $repo['full_name'],
                $issue['number'],
                $issue['title']
            )),
            [
                'type' => 'section',
                'text' => [
                    'type' => TextObject::TYPE_MARKDOWN,
                    'text' => $comment['body'],
                ],
            ],
        ], $this->createFieldsBlocks($repo, $issueUrl, (string) $issue['number'], $author));
    }

    private function createFieldsBlocks(array $repo, string $issueUrl, string $issueNumber, array $author): array
    {
        return [
            [
                'type'   => 'section',
                'fields' => [
                    [
                        'type' => TextObject::TYPE_MARKDOWN,
                        'text' => '*Repository*',
                    ],
                    [
                        'type' => TextObject::TYPE_MARKDOWN,
                        'text' => sprintf('*%s*', ucfirst($this->getIssueType())),
                    ],
                    [
                        'type' => TextObject::TYPE_MARKDOWN,
                        'text' => sprintf('<%s|%s>', $repo['html_url'], $repo['full_name']),
                    ],
                    [
                        'type' => TextObject::TYPE_MARKDOWN,
                        'text' => sprintf('<%s|#%s>', $issueUrl, $issueNumber),
                    ],
                ],
            ],
            [
                'type'   => 'section',
                'fields' => [
                    [
                        'type' => TextObject::TYPE_MARKDOWN,
                        'text' => '*Commenter*',
                    ],
                    [
                        'type' => TextObject::TYPE_MARKDOWN,
                        'text' => ' ',
                    ],
                    [
                        'type' => TextObject::TYPE_MARKDOWN,
                        'text' => sprintf('<%s|%s>', $author['html_url'], $author['login']),
                    ],
                ],
            ],
        ];
    }
}
