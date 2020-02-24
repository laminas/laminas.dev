<?php

declare(strict_types=1);

namespace App\Slack\Method;

use App\Slack\Domain\Block;
use App\Slack\Domain\BlockInterface;
use DomainException;

/**
 * https://api.slack.com/methods/chat.postMessage
 */
class ChatPostMessage implements ApiRequestInterface
{
    /** @var BlockInterface[] */
    private $blocks = [];

    /** @var string */
    private $channel;

    /** @var string */
    private $endpoint = 'chat.postMessage';

    /** @var string */
    private $method = 'POST';

    /** @var bool */
    private $mrkdwn = true;

    /** @var string */
    private $text = [];

    public function __construct(string $channel)
    {
        $this->channel = sprintf('#%s', ltrim($channel, '#'));
    }

    public function setText(string $text, bool $mrkdwn = true) : self
    {
        $this->text   = $text;
        $this->mrkdwn = $mrkdwn;

        return $this;
    }

    public function addBlock(BlockInterface $block) : self
    {
        $this->blocks[] = $block;

        return $this;
    }

    public function getMethod() : string
    {
        return $this->method;
    }

    public function getEndpoint() : string
    {
        return $this->endpoint;
    }

    public function toArray() : array
    {
        if (empty($this->text) && empty($this->blocks)) {
            throw new DomainException('Message must contain text, one or more blocks, or both');
        }

        $message = ['channel' => $this->channel];

        if (! empty($this->text)) {
            $message = array_merge($message, [
                'text'   => $this->text,
                'mrkdwn' => $this->mrkdwn,
            ]);
        }

        if (! empty($this->blocks)) {
            $message['blocks'] = array_reduce($this->blocks, function (array $serialized, Block $block) {
                $serialized[] = $block->toArray();
                return $serialized;
            }, []);
        }

        return $message;
    }
}
