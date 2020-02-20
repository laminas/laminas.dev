<?php

declare(strict_types=1);

namespace App\GitHub\Event;

use Assert\Assert;
use DateTimeImmutable;

use function in_array;
use function sprintf;

/**
 * @see https://developer.github.com/v3/activity/events/types/#issuecommentevent
 */
class GitHubIssueComment implements GitHubMessageInterface
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
        Assert::that($this->payload['comment'])->keyIsset('body');
        Assert::that($this->payload['comment'])->keyIsset('created_at');
        Assert::that($this->payload['comment'])->keyIsset('html_url');
        Assert::that($this->payload['comment'])->keyIsset('user');
        Assert::that($this->payload['comment']['user'])->isArray();
        Assert::that($this->payload['comment']['user'])->keyIsset('login');
        Assert::that($this->payload['comment']['user'])->keyIsset('html_url');
        Assert::that($this->payload['issue'])->isArray();
        Assert::that($this->payload['issue'])->keyIsset('html_url');
        Assert::that($this->payload['issue'])->keyIsset('number');
        Assert::that($this->payload['issue'])->keyIsset('title');
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

    public function getMessagePayload():  array
    {
        $payload = $this->payload;
        $comment = $payload['comment'];
        $author  = $comment['user'];
        $issue   = $payload['issue'];
        $repo    = $payload['repository'];
        $ts      = (new DateTimeImmutable($comment['created_at']))->getTimestamp();

        $issueType = isset($issue['pull_request']) ? self::TYPE_PULL_REQUEST : self::TYPE_ISSUE;
        $issueUrl  = $issueType === self::TYPE_PULL_REQUEST
            ? $issue['pull_request']['html_url']
            : $issue['html_url'];

        return [
            'fallback' => sprintf(
                '[%s] New comment by %s on %s #%s %s: %s',
                $repo['full_name'],
                $author['login'],
                $issueType,
                $issue['number'],
                $issue['title'],
                $comment['html_url']
            ),
            'color'   => '#FAD5A1',
            'pretext' => sprintf(
                '[<%s|#%s>] New comment by <%s|%s> on %s <%s|#%s %s>',
                $repo['html_url'],
                $repo['full_name'],
                $author['html_url'],
                $author['login'],
                $issueType,
                $comment['html_url'],
                $issue['number'],
                $issue['title']
            ),
            'author_name' => sprintf('%s (GitHub)', $repo['full_name']),
            'author_link' => $repo['html_url'],
            'author_icon' => self::GITHUB_ICON,
            'title'       => sprintf('Comment on %s %s#%s', $issueType, $repo['full_name'], $issue['number']),
            'title_link'  => $comment['html_url'],
            'text'        => $comment['body'],
            'fields'      => [
                [
                    'title' => 'Repository',
                    'value' => sprintf('<%s>', $repo['html_url']),
                    'short' => true,
                ],
                [
                    'title' => 'Commenter',
                    'value' => sprintf('<%s|%s>', $author['html_url'], $author['login']),
                    'short' => true,
                ],
                [
                    'title' => 'Issue',
                    'value' => sprintf('<%s|#%s %s>', $issueUrl, $issue['number'], $issue['title']),
                    'short' => true,
                ],
            ],
            'footer'      => 'GitHub',
            'footer_icon' => self::GITHUB_ICON,
            'ts'          => $ts,
        ];
    }
}
