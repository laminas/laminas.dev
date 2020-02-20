<?php

declare(strict_types=1);

namespace App\GitHub\Listener;

use App\GitHub\Event\GitHubPullRequest;
use App\Slack\Domain\Attachment;
use App\Slack\Method\ChatPostMessage;
use App\Slack\SlackClient;

class GitHubPullRequestListener
{
    /** @var string */
    private $channel;

    /** @var SlackClient */
    private $slackClient;

    public function __construct(string $channel, SlackClient $slackClient)
    {
        $this->channel     = $channel;
        $this->slackClient = $slackClient;
    }

    public function __invoke(GitHubPullRequest $pullRequest) : void
    {
        $notification = new ChatPostMessage($this->channel);
        $notification->addAttachment(new Attachment($pullRequest->getMessagePayload()));
        $this->slackClient->sendApiRequest($notification);
    }
}
