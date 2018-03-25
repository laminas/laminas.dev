<?php

declare(strict_types=1);

namespace App\GitHub\Message;

use Assert\Assert;
use function in_array;
use function sprintf;
use function substr;

/**
 * @see https://developer.github.com/v3/activity/events/types/#statusevent
 * @see https://github.com/integrations/slack/blob/master/lib/messages/status.js
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
        Assert::that($this->payload['description'])->nullOr()->string();
        Assert::that($this->payload['target_url'])->nullOr()->url();
        Assert::that($this->payload['branches'])->nullOr()->isArray();
    }

    public function ignore() : bool
    {
        return ! in_array($this->payload['state'], [
            'success',
            'failure',
            'error',
        ], true);
    }

    public function getBranch() : ?string
    {
        /** @var array $branches */
        $branches = $this->payload['branches'] ?? null;
        if (! $branches) {
            return null;
        }

        foreach ($branches as $branch) {
            if (! isset($branch['name'], $branch['commit']['sha'])) {
                continue;
            }

            if ($this->payload['sha'] === $branch['commit']['sha']) {
                return $branch['name'];
            }
        }

        return null;
    }

    public function getSummary() : string
    {
        return sprintf('%s: %s', $this->payload['context'] ?? '', $this->payload['description'] ?? '');
    }

    public function getState() : string
    {
        return $this->payload['state'];
    }

    public function getCommit() : ?string
    {
        $commit = $this->payload['commit'] ?? null;
        if (! $commit) {
            return null;
        }

        return sprintf(
            '<%s|`%s`> - %s (<https://github.com/%s|`%s`>)',
            $this->payload['target_url'] ?? $commit['url'],
            substr($commit['sha'], 0, 8),
            $commit['commit']['message'],
            $this->payload['repository']['full_name'],
            $this->getBranch() ?? 'unknown'
        );
    }
}
