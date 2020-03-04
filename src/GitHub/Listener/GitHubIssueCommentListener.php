<?php

declare(strict_types=1);

namespace App\GitHub\Listener;

use App\GitHub\Event\GitHubIssueComment;
use App\Slack\Domain\Block;
use App\Slack\Domain\WebAPIMessage;
use App\Slack\SlackClientInterface;

class GitHubIssueCommentListener
{
    /** @var string */
    private $channel;

    /** @var SlackClientInterface */
    private $slackClient;

    public function __construct(string $channel, SlackClientInterface $slackClient)
    {
        $this->channel     = $channel;
        $this->slackClient = $slackClient;
    }

    public function __invoke(GitHubIssueComment $comment): void
    {
        $notification = new WebAPIMessage();
        $notification->setChannel($this->channel);
        $notification->setText($comment->getFallbackMessage());
        foreach ($comment->getMessageBlocks() as $blockData) {
            $notification->addBlock(Block::create($blockData));
        }
        $this->slackClient->sendWebAPIMessage($notification);
    }
}
