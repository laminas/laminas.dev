<?php

declare(strict_types=1);

namespace App\GitHub\Event;

use Assert\Assert;
use function in_array;
use function sprintf;

/**
 * @see https://developer.github.com/v3/activity/events/types/#pullrequestevent
 * @see https://github.com/integrations/slack/blob/master/lib/messages/pull-request.js
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
        Assert::that($this->payload)->keyIsset('pull_request');
        Assert::that($this->payload['pull_request'])->keyIsset('user');
        Assert::that($this->payload['pull_request']['user'])->keyIsset('login');
        Assert::that($this->payload['pull_request'])->keyIsset('base');
        Assert::that($this->payload['pull_request']['base'])->keyIsset('label');
        Assert::that($this->payload['pull_request'])->keyIsset('head');
        Assert::that($this->payload['pull_request']['head'])->keyIsset('label');
        Assert::that($this->payload['pull_request'])->keyIsset('title');
    }

    public function ignore() : bool
    {
        return ! in_array($this->payload['action'], [
            'opened',
            'edited',
            'closed',
            'reopened',
        ], true);
    }

    public function getSummary() : string
    {
        return sprintf(
            '[%s] Pull request %s by %s',
            $this->payload['repository']['full_name'],
            $this->payload['action'],
            $this->payload['pull_request']['user']['login']
        );
    }

    public function getCommitMessage() : string
    {
        return sprintf(
            'Merge into %s from %s',
            $this->payload['pull_request']['base']['label'],
            $this->payload['pull_request']['head']['label']
        );
    }

    public function getTitle() : string
    {
        return $this->payload['pull_request']['title'];
    }

    public function getTitleLink() : string
    {
        return $this->payload['pull_request']['html_url'];
    }

    public function getBody() : string
    {
        return $this->payload['pull_request']['body'] ?? '';
    }

    public function getFooter() : string
    {
        return sprintf(
            '<%s|%s>',
            $this->payload['pull_request']['html_url'],
            $this->payload['repository']['full_name']
        );
    }
}
