<?php

declare(strict_types=1);

namespace App\Slack\Method;

use App\Slack\Domain\Attachment;
use DomainException;
use function implode;

/**
 * https://api.slack.com/methods/chat.postMessage
 */
class ChatPostMessage implements ApiRequestInterface
{
    /** @var string */
    private $method = 'POST';

    /** @var string */
    private $endpoint = 'chat.postMessage';

    /** @var string */
    private $channel;

    /** @var string[] */
    private $text = [];

    /** @var Attachment[] */
    private $attachments = [];

    /** @var bool */
    private $mrkdwn = true;

    /** @var null|string */
    private $username;

    public function __construct(string $channel, ?string $text = null)
    {
        $this->channel = $channel;

        if (! $text) {
            return;
        }

        $this->text[] = $text;
    }

    public function addText(string $text, ?bool $mrkdwn = null) : self
    {
        $this->text[] = $text;
        $this->mrkdwn = $mrkdwn ?? true;

        return $this;
    }

    public function withUsername(string $username) : self
    {
        $this->username = $username;

        return $this;
    }

    public function addAttachment(Attachment $attachment) : self
    {
        $this->attachments[] = $attachment;

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
        if (empty($this->text) && empty($this->attachments)) {
            throw new DomainException('Message must contain text, attachment(s) or both');
        }

        $message = [
            'channel'  => $this->channel,
            'text'     => implode("\n", $this->text),
            'mrkdwn'   => $this->mrkdwn,
            'username' => $this->username,
        ];

        foreach ($this->attachments as $attachment) {
            $message['attachments'][] = $attachment->toArray();
        }

        return $message;
    }
}
