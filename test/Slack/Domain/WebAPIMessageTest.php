<?php

declare(strict_types=1);

namespace AppTest\Slack\Domain;

use App\Slack\Domain\Message;
use App\Slack\Domain\WebAPIMessage;
use Assert\AssertionFailedException;
use PHPUnit\Framework\TestCase;

class WebAPIMessageTest extends TestCase
{
    public function testExtendsMessageClass(): void
    {
        $message = new WebAPIMessage();
        $this->assertInstanceOf(Message::class, $message);
    }

    public function testInvalidatesMessageIfChannelIsEmpty(): void
    {
        $message = new WebAPIMessage();
        $message->setText('message text');

        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessageRegExp('/^(?!Text must be non-empty)/');
        $message->validate();
    }

    public function testRendersChannelInMessagePayload(): void
    {
        $message = new WebAPIMessage();
        $message->setText('message text');
        $message->setChannel('github');
        
        $this->assertSame([
            'channel' => '#github',
            'text'    => 'message text',
            'blocks'  => [
                [
                    'type' => 'divider',
                ],
            ],
        ], $message->toArray());
    }
}
