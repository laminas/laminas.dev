<?php

declare(strict_types=1);

namespace AppTest\Slack\Domain;

use App\Slack\Domain\Message;
use App\Slack\Domain\SlashResponseMessage;
use Assert\AssertionFailedException;
use PHPUnit\Framework\TestCase;

class SlashResponseMessageTest extends TestCase
{
    public function testExtendsMessageClass(): void
    {
        $message = new SlashResponseMessage();
        $this->assertInstanceOf(Message::class, $message);
    }

    public function testMarksMessageInvalidIfResponseTypeIsUnknown(): void
    {
        $message = new SlashResponseMessage();
        $message->setText('message text');
        $message->setResponseType('unknown-response-type');

        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessageRegExp('/^(?!Text must be non-empty)/');
        $message->validate();
    }

    public function testRepresentationDoesNotIncludeResponseTypeIfEphemeral(): void
    {
        $message = new SlashResponseMessage();
        $message->setText('message text');

        $this->assertSame([
            'text' => 'message text',
        ], $message->toArray());
    }

    public function testRepresentationIncludesResponseTypeIfInChannel(): void
    {
        $message = new SlashResponseMessage();
        $message->setText('message text');
        $message->setResponseType(SlashResponseMessage::TYPE_IN_CHANNEL);

        $this->assertSame([
            'text' => 'message text',
            'response_type' => SlashResponseMessage::TYPE_IN_CHANNEL,
        ], $message->toArray());
    }
}
