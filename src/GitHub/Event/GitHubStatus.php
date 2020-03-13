<?php

declare(strict_types=1);

namespace App\GitHub\Event;

use App\GitHub\Listener\PullRequest;
use App\Slack\Domain\TextObject;
use Assert\Assert;

use function array_shift;
use function in_array;
use function preg_match;
use function sprintf;
use function stristr;
use function strpos;
use function substr;

/**
 * @see https://developer.github.com/v3/activity/events/types/#statusevent
 */
final class GitHubStatus extends AbstractGitHubEvent
{
    private const CONTEXT_PATTERNS = [
        '#^github#',
        '#travis-ci#',
        '#coveralls#',
    ];

    private const STATES_ALLOWED = [
        'success',
        'failure',
        'error',
    ];

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
        if (! in_array($this->payload['state'], self::STATES_ALLOWED, true)) {
            return true;
        }

        foreach (self::CONTEXT_PATTERNS as $regex) {
            if (preg_match($regex, (string) $this->payload['context'])) {
                return false;
            }
        }

        return true;
    }

    public function getBranch(): string
    {
        $branches = $this->payload['branches'] ?? [];
        if (empty($branches)) {
            return 'unknown';
        }
        $branch = array_shift($branches);
        return $branch['name'] ?? 'unknown';
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
            $this->createContextBlock($payload['target_url'], sprintf(
                '<%s|Build %s> for <%s|%s>@%s (<%s|%s>)',
                $payload['target_url'],
                $this->getBuildStatus(),
                $repo['html_url'],
                $repo['full_name'],
                $branch,
                $commit['html_url'],
                substr($payload['sha'], 0, 8)
            )),
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
            $this->createContextBlock($pullRequest->getUrl(), sprintf(
                '<%s|Build %s> for pull request <%s|%s#%s %s>',
                $payload['target_url'],
                $this->getBuildStatus(),
                $pullRequest->getUrl(),
                $repo['full_name'],
                $pullRequest->getNumber(),
                $pullRequest->getTitle(),
            )),
            $this->createFieldsBlock($repo, 'Pull Request', sprintf(
                '<%s|#%s %s>',
                $pullRequest->getUrl(),
                $pullRequest->getNumber(),
                $pullRequest->getTitle()
            )),
        ];
    }

    protected function createContextBlock(string $url, ?string $additionalText = null): array
    {
        return [
            'type'     => 'context',
            'elements' => [
                [
                    'type'      => 'image',
                    'image_url' => $this->payload['avatar_url'],
                    'alt_text'  => $this->discoverContext(),
                ],
                [
                    'type' => TextObject::TYPE_MARKDOWN,
                    'text' => sprintf(
                        '<%s|*GitHub*>',
                        $this->payload['target_url'],
                        $this->getAuthorName()
                    ),
                ],
                [
                    'type' => TextObject::TYPE_MARKDOWN,
                    'text' => $additionalText ?? ' ',
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
                    'text' => sprintf("*Repository*\n<%s|%s>", $repo['html_url'], $repo['full_name']),
                ],
                [
                    'type' => TextObject::TYPE_MARKDOWN,
                    'text' => sprintf("*Status*\n%s", $this->getBuildStatus()),
                ],
                [
                    'type' => TextObject::TYPE_MARKDOWN,
                    'text' => sprintf("*%s*\n%s", $extraLabel, $extraValue),
                ],
            ],
        ];
    }

    private function discoverContext(): string
    {
        $context = $this->payload['context'] ?? '';

        if (stristr($context, 'travis') !== false) {
            return 'Travis-CI';
        }

        if (stristr($context, 'coveralls') !== false) {
            return 'Coveralls';
        }

        return 'GitHub';
    }
}
