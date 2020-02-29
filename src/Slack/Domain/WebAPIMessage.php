<?php

declare(strict_types=1);

namespace App\Slack\Domain;

use Assert\Assert;

class WebAPIMessage extends Message
{
    /** @var string */
    private $channel;

    public function setChannel(string $channel): void
    {
        $this->channel = sprintf('#%s', ltrim($channel, '#'));
    }

    public function validate(): void
    {
        Assert::that($this->channel)->string()->notEmpty();
        parent::validate();
    }

    public function toArray(): array
    {
        return array_merge([
            'channel' => $this->channel,
        ], parent::toArray());
    }
}
