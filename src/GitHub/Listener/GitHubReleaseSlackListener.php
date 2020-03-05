<?php

declare(strict_types=1);

namespace App\GitHub\Listener;

use App\GitHub\Event\GitHubRelease;
use App\Slack\Domain\Block;
use App\Slack\Domain\WebAPIMessage;
use App\Slack\SlackClientInterface;

class GitHubReleaseSlackListener
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

    public function __invoke(GitHubRelease $release): void
    {
        $notification = new WebAPIMessage();
        $notification->setChannel($this->channel);
        $notification->setText($release->getFallbackMessage());
        foreach ($release->getMessageBlocks() as $block) {
            $notification->addBlock(Block::create($block));
        }
        $this->slackClient->sendWebAPIMessage($notification);
    }
}
