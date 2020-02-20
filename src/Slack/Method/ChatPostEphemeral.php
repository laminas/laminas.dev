<?php

declare(strict_types=1);

namespace App\Slack\Method;

use App\Slack\Domain\Attachment;
use DomainException;
use function implode;

/**
 * https://api.slack.com/methods/chat.postEphemeral
 */
class ChatPostEphemeral implements ApiRequestInterface
{
    /** @var string */
    private $method = 'POST';

    /** @var string */
    private $endpoint = 'chat.postEphemeral';

    /** @var string */
    private $channel;

    /** @var string */
    private $user;

    /** @var string[] */
    private $text = [];

    /** @var Attachment[] */
    private $attachments = [];

    /** @var bool */
    private $mrkdwn = true;

    /** @var bool */
    private $asUser = false;

    /** @var null|string */
    private $username;

    public function __construct(string $channel, string $user, ?string $text = null)
    {
        $this->channel = sprintf('#%s', ltrim($channel, '#'));
        $this->user    = $user;

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

    public function asUser(bool $asUser) : self
    {
        $this->asUser = $asUser;

        if ($asUser === true) {
            $this->username = null;
        }

        return $this;
    }

    public function withUsername(string $username) : self
    {
        $this->username = $username;
        $this->asUser   = false;

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
            'user'     => $this->user,
            'text'     => implode("\n", $this->text),
            'mrkdwn'   => $this->mrkdwn,
            'as_user'  => $this->asUser,
            'username' => $this->username,
        ];

        foreach ($this->attachments as $attachment) {
            $message['attachments'][] = $attachment->toArray();
        }

        return $message;
    }
}
