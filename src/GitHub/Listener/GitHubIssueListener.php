<?php

declare(strict_types=1);

namespace App\GitHub\Listener;

use App\GitHub\Event\GitHubIssue;
use App\Slack\Domain\Block;
use App\Slack\Domain\WebAPIMessage;
use App\Slack\SlackClientInterface;

class GitHubIssueListener
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

    public function __invoke(GitHubIssue $issue): void
    {
        $notification = new WebAPIMessage();
        $notification->setChannel($this->channel);
        $notification->setText($issue->getFallbackMessage());
        foreach ($issue->getMessageBlocks() as $blockData) {
            $notification->addBlock(Block::create($blockData));
        }
        $this->slackClient->sendWebAPIMessage($notification);
    }
}
