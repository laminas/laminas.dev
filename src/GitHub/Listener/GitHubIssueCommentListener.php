<?php

declare(strict_types=1);

namespace App\GitHub\Listener;

use App\GitHub\Event\GitHubIssueComment;
use App\Slack\Domain\Attachment;
use App\Slack\Method\ChatPostMessage;
use App\Slack\SlackClient;

class GitHubIssueCommentListener
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

    public function __invoke(GitHubIssueComment $comment) : void
    {
        $notification = new ChatPostMessage($this->channel);
        $notification->addAttachment(new Attachment($comment->getMessagePayload()));
        $this->slackClient->sendApiRequest($notification);
    }
}
