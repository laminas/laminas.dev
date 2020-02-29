<?php

declare(strict_types=1);

namespace App\GitHub\Listener;

use App\GitHub\Event\GitHubPullRequest;
use App\Slack\Domain\Block;
use App\Slack\Domain\WebAPIMessage;
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
        $notification = new WebAPIMessage();
        $notification->setChannel($this->channel);
        $notification->setText($pullRequest->getFallbackMessage());
        foreach ($pullRequest->getMessageBlocks() as $block) {
            $notification->addBlock(Block::create($block));
        }
        $this->slackClient->sendWebAPIMessage($notification);
    }
}
