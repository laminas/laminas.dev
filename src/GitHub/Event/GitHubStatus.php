<?php

declare(strict_types=1);

namespace App\GitHub\Event;

use App\GitHub\Listener\PullRequest;
use Assert\Assert;
use DateTimeImmutable;

use function in_array;
use function sprintf;
use function substr;

/**
 * @see https://developer.github.com/v3/activity/events/types/#statusevent
 */
final class GitHubStatus implements GitHubMessageInterface
{
    /** @var array */
    private $payload;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    public function validate() : void
    {
        Assert::that($this->payload['sha'])->notEmpty()->string();
        Assert::that($this->payload['state'])->notEmpty()->string();
        Assert::that($this->payload['context'])->nullOr()->string();
        Assert::that($this->payload['target_url'])->nullOr()->url();
        Assert::that($this->payload['avatar_url'])->nullOr()->url();
        Assert::that($this->payload['branches'])->nullOr()->isArray();
        Assert::that($this->payload['commit'])->isArray();
        Assert::that($this->payload['commit'])->keyIsset('html_url');
        Assert::that($this->payload['repository'])->isArray();
        Assert::that($this->payload['repository'])->keyIsset('full_name');
        Assert::that($this->payload['updated_at'])->notEmpty()->string();
    }

    public function ignore() : bool
    {
        return ! in_array($this->payload['state'], [
            'success',
            'failure',
            'error',
        ], true);
    }

    public function isForPullRequest(): bool
    {
        return (bool) preg_match('#/pr$#', $this->payload['context']);
    }

    public function getBranch(): string
    {
        return $this->payload['branches'][0]['name'];
    }

    public function getCommitIdentifier(): string
    {
        return $this->payload['sha'];
    }

    public function getRepository(): string
    {
        return $this->payload['repository']['full_name'];
    }

    public function getAuthorName(): string
    {
        if (strpos($this->payload['context'], 'travis-ci') !== false) {
            return 'Travis CI';
        }

        if (strpos($this->payload['context'], 'github') !== false) {
            return 'GitHub Actions';
        }

        return 'GitHub';
    }

    public function getBuildStatus(): string
    {
        switch ($payload['state']) {
            case 'success':
                return 'passed';
            case 'error':
                return 'errored';
            case 'failed':
            default:
                return 'failed';
        }
    }

    public function getMessageColor(): string
    {
        switch ($payload['state']) {
            case 'success':
                return 'good';
            case 'error':
                return 'danger';
            case 'failed':
            default:
                return 'danger';
        }
    }

    public function getMessagePayload(): array
    {
        $payload    = $this->payload;
        $repo       = $payload['repository'];
        $branch     = $this->getBranch();
        $commit     = $payload['commit'];
        $authorName = $this->getAuthorName();

        return [
            'fallback' => sprintf(
                'Build %s for %s@%s (%s): %s',
                $this->getBuildStatus(),
                $repo['full_name'],
                $branch,
                substr($payload['sha'], 0, 8),
                $payload['target_url']
            ),
            'color'       => $this->getMessageColor(),
            'author_name' => $authorName,
            'author_link' => $payload['target_url'],
            'author_icon' => $payload['avatar_url'],
            'text'        => sprintf(
                '<%s|Build %s> for <%s|%s>@%s (<%s|%s>)',
                $payload['target_url'],
                $this->getBuildStatus(),
                $repo['html_url'],
                $repo['full_name'],
                $branch,
                $commit['html_url'],
                substr($payload['sha'], 0, 8)
            ),
            'fields'      => [
                [
                    'title' => 'Repository',
                    'value' => sprintf('<%s|%s>', $repo['html_url'], $repo['full_name']),
                    'short' => true,
                ],
                [
                    'title' => 'Status',
                    'value' => $this->getBuildStatus(),
                    'short' => true,
                ],
                [
                    'title' => 'Branch',
                    'value' => sprintf('%s (%s)', $branch, substr($payload['sha'], 0, 8)),
                    'short' => true,
                ],
            ],
            'footer'      => $authorName,
            'footer_icon' => $payload['avatar_url'],
            'ts'          => (new DateTimeImmutable($payload['updated_at']))->getTimestamp(),
        ];
    }

    public function prepareMessagePayloadForPullRequestStatus(PullRequest $pullRequest): array
    {
        $payload    = $this->payload;
        $repo       = $payload['repository'];
        $authorName = $this->getAuthorName();

        return [
            'fallback' => sprintf(
                '[%s] Build %s for pull request #%s %s: %s',
                $repo['full_name'],
                $this->getBuildStatus(),
                $pullRequest->getNumber(),
                $pullRequest->getTitle(),
                $payload['target_url']
            ),
            'color'       => $this->getMessageColor(),
            'author_name' => $authorName,
            'author_link' => $payload['target_url'],
            'author_icon' => $payload['avatar_url'],
            'text'        => sprintf(
                '<%s|Build %s> for pull request <%s|%s#%s %s>',
                $payload['target_url'],
                $this->getBuildStatus(),
                $pullRequest->getUrl(),
                $repo['full_name'],
                $pullRequest->getNumber(),
                $pullRequest->getTitle(),
            ),
            'fields'      => [
                [
                    'title' => 'Repository',
                    'value' => sprintf('<%s|%s>', $repo['html_url'], $repo['full_name']),
                    'short' => true,
                ],
                [
                    'title' => 'Status',
                    'value' => $this->getBuildStatus(),
                    'short' => true,
                ],
                [
                    'title' => 'Pull Request',
                    'value' => sprintf(
                        '<%s|#%s %s>',
                        $pullRequest->getUrl(),
                        $pullRequest->getNumber(),
                        $pullRequest->getTitle()
                    ),
                    'short' => true,
                ],
            ],
            'footer'      => $authorName,
            'footer_icon' => $payload['avatar_url'],
            'ts'          => (new DateTimeImmutable($payload['updated_at']))->getTimestamp(),
        ];
    }
}
