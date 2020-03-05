<?php

declare(strict_types=1);

namespace App\Discourse\Listener;

use App\Discourse\Event\DiscoursePost;
use App\Slack\Domain\Block;
use App\Slack\Domain\WebAPIMessage;
use App\Slack\SlackClientInterface;

class DiscoursePostListener
{
    /** @var SlackClientInterface */
    private $slack;

    public function __construct(SlackClientInterface $slack)
    {
        $this->slack = $slack;
    }

    public function __invoke(DiscoursePost $post): void
    {
        if (! $post->isValidForSlack()) {
            return;
        }

        $message = new WebAPIMessage();
        $message->setChannel($post->getChannel());
        $message->setText($post->getFallbackMessage());
        foreach ($post->getMessageBlocks() as $blockData) {
            $message->addBlock(Block::create($blockData));
        }

        $this->slack->sendWebAPIMessage($message);
    }
}
