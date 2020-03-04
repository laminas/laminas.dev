<?php

declare(strict_types=1);

namespace App\GitHub\Event;

use App\GitHub\Listener\PullRequest;
use App\Slack\Domain\TextObject;
use Assert\Assert;

use function in_array;
use function preg_match;
use function sprintf;
use function strpos;
use function substr;

/**
 * @see https://developer.github.com/v3/activity/events/types/#statusevent
 */
final class GitHubStatus extends AbstractGitHubEvent
{
    /** @var array */
    private $payload;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    public function validate(): void
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

    public function ignore(): bool
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
        switch ($this->payload['state']) {
            case 'success':
                return 'passed';
            case 'error':
                return 'errored';
            case 'failed':
            default:
                return 'failed';
        }
    }

    public function getFallbackMessage(?PullRequest $pullRequest = null): string
    {
        return $pullRequest
            ? $this->getFallbackMessageForPullRequest($pullRequest)
            : $this->getDefaultFallbackMessage();
    }

    private function getDefaultFallbackMessage(): string
    {
        $payload = $this->payload;
        $repo    = $payload['repository'];

        return sprintf(
            'Build %s for %s@%s (%s): %s',
            $this->getBuildStatus(),
            $repo['full_name'],
            $this->getBranch(),
            substr($payload['sha'], 0, 8),
            $payload['target_url']
        );
    }

    private function getFallbackMessageForPullRequest(PullRequest $pullRequest): string
    {
        $payload = $this->payload;
        $repo    = $payload['repository'];

        return sprintf(
            '[%s] Build %s for pull request #%s %s: %s',
            $repo['full_name'],
            $this->getBuildStatus(),
            $pullRequest->getNumber(),
            $pullRequest->getTitle(),
            $pullRequest->getUrl()
        );
    }

    public function getMessageBlocks(?PullRequest $pullRequest = null): array
    {
        return $pullRequest
            ? $this->getMessageBlocksForPullRequest($pullRequest)
            : $this->getDefaultMessageBlocks();
    }

    private function getDefaultMessageBlocks(): array
    {
        $payload = $this->payload;
        $repo    = $payload['repository'];
        $branch  = $this->getBranch();
        $commit  = $payload['commit'];

        return [
            $this->createContextBlock($payload['target_url']),
            [
                'type' => 'section',
                'text' => [
                    'type' => TextObject::TYPE_MARKDOWN,
                    'text' => sprintf(
                        '<%s|Build %s> for <%s|%s>@%s (<%s|%s>)',
                        $payload['target_url'],
                        $this->getBuildStatus(),
                        $repo['html_url'],
                        $repo['full_name'],
                        $branch,
                        $commit['html_url'],
                        substr($payload['sha'], 0, 8)
                    ),
                ],
            ],
            $this->createFieldsBlock($repo, 'Branch', sprintf(
                '%s (%s)',
                $branch,
                substr($payload['sha'], 0, 8)
            )),
        ];
    }

    private function getMessageBlocksForPullRequest(PullRequest $pullRequest): array
    {
        $payload = $this->payload;
        $repo    = $payload['repository'];

        return [
            $this->createContextBlock($pullRequest->getUrl()),
            [
                'type' => 'section',
                'text' => [
                    'type' => TextObject::TYPE_MARKDOWN,
                    'text' => sprintf(
                        '<%s|Build %s> for pull request <%s|%s#%s %s>',
                        $payload['target_url'],
                        $this->getBuildStatus(),
                        $pullRequest->getUrl(),
                        $repo['full_name'],
                        $pullRequest->getNumber(),
                        $pullRequest->getTitle(),
                    ),
                ],
            ],
            $this->createFieldsBlock($repo, 'Pull Request', sprintf(
                '<%s|#%s %s>',
                $pullRequest->getUrl(),
                $pullRequest->getNumber(),
                $pullRequest->getTitle()
            )),
        ];
    }

    protected function createContextBlock(string $url): array
    {
        return [
            'type'     => 'context',
            'elements' => [
                [
                    'type'      => 'image',
                    'image_url' => $this->payload['avatar_url'],
                    'alt_text'  => 'GitHub',
                ],
                [
                    'type' => TextObject::TYPE_MARKDOWN,
                    'text' => sprintf(
                        '<%s|*GitHub*>',
                        $this->payload['target_url'],
                        $this->getAuthorName()
                    ),
                ],
            ],
        ];
    }

    private function createFieldsBlock(array $repo, string $extraLabel, string $extraValue): array
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
                    'text' => '*Status*',
                ],
                [
                    'type' => TextObject::TYPE_MARKDOWN,
                    'text' => sprintf('*%s*', $extraLabel),
                ],
                [
                    'type' => TextObject::TYPE_MARKDOWN,
                    'text' => sprintf('<%s|%s>', $repo['html_url'], $repo['full_name']),
                ],
                [
                    'type' => TextObject::TYPE_MARKDOWN,
                    'text' => $this->getBuildStatus(),
                ],
                [
                    'type' => TextObject::TYPE_MARKDOWN,
                    'text' => $extraValue,
                ],
            ],
        ];
    }
}
