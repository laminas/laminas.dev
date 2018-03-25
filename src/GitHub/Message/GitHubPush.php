<?php

declare(strict_types=1);

namespace App\GitHub\Message;

use Assert\Assert;
use Generator;
use function count;
use function sprintf;
use function str_replace;
use function substr;

/**
 * @see https://developer.github.com/v3/activity/events/types/#pushevent
 * @see https://github.com/integrations/slack/blob/master/lib/messages/push.js
 */
final class GitHubPush implements GitHubMessageInterface
{
    /** @var string[][] */
    private $payload;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    public function validate() : void
    {
        Assert::that($this->payload)->keyIsset('ref');
        Assert::that($this->payload)->keyIsset('compare');
        Assert::that($this->payload)->keyIsset('repository');
        Assert::that($this->payload['repository'])->isArray();
        Assert::that($this->payload['repository'])->keyIsset('full_name');
        Assert::that($this->payload['repository'])->keyIsset('html_url');
        Assert::that($this->payload)->keyIsset('commits');
        foreach ($this->payload['commits'] as $commit) {
            Assert::that($commit)->keyIsset('url');
            Assert::that($commit)->keyIsset('message');
        }
    }

    public function ignore() : bool
    {
        return false;
    }

    public function getSummary() : string
    {
        $noOfCommits = count($this->payload['commits'] ?? []);
        $commits     = $noOfCommits === 1 ? 'commit' : 'commits';
        $branch      = str_replace('refs/heads/', '', $this->payload['ref'] ?? '');
        $pushed      = $this->payload['forced'] ?? false;

        return sprintf(
            '<%s|%d new %s> %s to `<%s/commits/%s|%s>`',
            $this->payload['compare'] ?? '',
            $noOfCommits,
            $commits,
            $pushed ? 'force-pushed' : 'pushed',
            $this->payload['repository']['html_url'],
            $branch,
            $branch
        );
    }

    public function getCommits() : Generator
    {
        $commits = $this->payload['commits'] ?? [];
        foreach ($commits as $commit) {
            yield sprintf(
                '<%s|`%s`> - %s',
                $commit['url'],
                substr($commit['sha'] ?? $commit['id'], 0, 8),
                $commit['message']
            );
        }
    }

    public function getFooter() : string
    {
        return sprintf(
            '<%s|%s>',
            $this->payload['repository']['html_url'],
            $this->payload['repository']['full_name']
        );
    }
}
