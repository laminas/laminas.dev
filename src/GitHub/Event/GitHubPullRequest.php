<?php

declare(strict_types=1);

namespace App\GitHub\Event;

use Assert\Assert;
use function in_array;
use function sprintf;

/**
 * @see https://developer.github.com/v3/activity/events/types/#pullrequestevent
 */
final class GitHubPullRequest implements GitHubMessageInterface
{
    /** @var array */
    private $payload;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    public function validate() : void
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

    public function ignore() : bool
    {
        return ! in_array($this->payload['action'], [
            'opened',
            'closed',
            'reopened',
        ], true);
    }

    public function getMessagePayload():  array
    {
        $payload = $this->payload;
        $pr      = $payload['pull_request'];
        $author  = $payload['sender'];
        $repo    = $payload['repository'];
        $action  = $payload['action'] === 'closed' && isset($pr['merged'])
            ? 'merged'
            : $payload['action'];

        switch ($action)
        {
            case 'closed':
                $ts = (new DateTimeImmutable($pr['closed_at']))->getTimestamp();
                break;
            case 'merged':
                $ts = (new DateTimeImmutable($pr['merged_at']))->getTimestamp();
                break;
            case 'reopened':
                $ts = (new DateTimeImmutable($pr['updated_at']))->getTimestamp();
                break;
            case 'opened':
            default:
                $ts = (new DateTimeImmutable($pr['created_at']))->getTimestamp();
                break;
        }

        return [
            'fallback' => sprintf(
                '[%s] Pull request %s by %s: %s',
                $repo['full_name'],
                $action,
                $author['login'],
                $pr['html_url']
            ),
            'color'   => '#E3E4E6',
            'pretext' => sprintf(
                '[<%s|#%s>] Pull request %s by <%s|%s>',
                $repo['html_url'],
                $repo['full_name'],
                $action,
                $author['html_url'],
                $author['login']
            ),
            'author_name' => sprintf('%s (GitHub)', $repo['full_name']),
            'author_link' => $repo['html_url'],
            'author_icon' => self::GITHUB_ICON,
            'title'       => sprintf(
                'Pull request %s: %s#%s %s',
                $action,
                $repo['full_name'],
                $pr['number'],
                $pr['title']
            ),
            'title_link'  => $pr['html_url'],
            'text'        => $action === 'created' ? $pr['body'] : '',
            'fields'      => [
                [
                    'title' => 'Repository',
                    'value' => sprintf('<%s>', $repo['html_url']),
                    'short' => true,
                ],
                [
                    'title' => 'Reporter',
                    'value' => sprintf('<%s|%s>', $author['html_url'], $author['login']),
                    'short' => true,
                ],
                [
                    'title' => 'Status',
                    'value' => $action,
                    'short' => true,
                ],
            ],
            'footer'      => 'GitHub',
            'footer_icon' => self::GITHUB_ICON,
            'ts'          => $ts,
        ];
    }
}
