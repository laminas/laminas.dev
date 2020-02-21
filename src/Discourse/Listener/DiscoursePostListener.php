<?php

declare(strict_types=1);

namespace App\Discourse\Listener;

use App\Discourse\Event\DiscoursePost;
use App\Slack\Domain\Block;
use App\Slack\Method\ChatPostMessage;
use App\Slack\SlackClient;

class DiscoursePostListener
{
    /** @var SlackClient */
    private $slack;

    public function __construct(SlackClient $slack)
    {
        $this->slack = $slack;
    }

    public function __invoke(DiscoursePost $post): void
    {
        if (! $post->isValidForSlack()) {
            return;
        }

        $message = new ChatPostMessage($post->getChannel());
        $message->setText($post->getFallbackMessage());
        $message->addAttachment(new Attachment($post->getMessagePayload()));
        foreach ($post->getMessageBlocks() as $blockData) {
            $message->addBlock(Block::create($blockData));
        }

        $this->slack->sendApiRequest($message);
    }
}
