<?php

declare(strict_types=1);

namespace App\GitHub\Message;

use Assert\Assert;
use function in_array;
use function sprintf;

/**
 * @see https://developer.github.com/v3/activity/events/types/#issuesevent
 */
class GitHubIssue implements GitHubMessageInterface
{
    /** @var array */
    private $payload;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    public function validate() : void
    {
        Assert::that($this->payload['action'])->notEmpty()->string();
        Assert::that($this->payload['issue'])->isArray();
        Assert::that($this->payload['issue'])->keyIsset('html_url');
        Assert::that($this->payload['issue'])->keyIsset('number');
        Assert::that($this->payload['issue'])->keyIsset('title');
        Assert::that($this->payload['issue'])->keyIsset('body');
        Assert::that($this->payload['issue'])->keyIsset('assignees');
        Assert::that($this->payload['issue'])->keyIsset('state');
        Assert::that($this->payload['issue']['assignees'])->nullOr()->isArray();
        Assert::that($this->payload['repository'])->isArray();
        Assert::that($this->payload['repository'])->keyIsset('full_name');
    }

    public function ignore() : bool
    {
        return ! in_array($this->payload['action'], [
            'assigned',
            'unassigned',
            'closed',
            'reopened',
            'edited',
        ], true);
    }

    public function getAction() : string
    {
        return $this->payload['action'];
    }

    public function getCode() : string
    {
        return sprintf('[%s#%s]', $this->payload['repository']['full_name'], $this->payload['issue']['number']);
    }

    public function getUrl() : string
    {
        return $this->payload['issue']['html_url'];
    }

    public function getTitle() : string
    {
        return $this->payload['issue']['title'];
    }

    public function getBody() : string
    {
        return $this->payload['issue']['body'];
    }

    public function isAssignedTo(string $login) : bool
    {
        foreach ($this->payload['issue']['assignees'] as $assignee) {
            if ($assignee['login'] === $login) {
                return true;
            }
        }

        return false;
    }

    public function isClosed() : bool
    {
        return $this->payload['issue']['state'] === 'closed';
    }
}
