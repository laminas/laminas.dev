<?php

declare(strict_types=1);

namespace AppTest\Slack\Listener;

use App\Slack\Domain\SlashResponseMessage;
use App\Slack\Event\TwitterReply;
use App\Slack\Listener\TwitterReplyListener;
use App\Slack\SlackClientInterface;
use Laminas\Twitter\Twitter;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use RuntimeException;

class TwitterReplyListenerTest extends TestCase
{
    /** @var TwitterReplyListener */
    private $listener;

    /** @var SlackClientInterface|ObjectProphecy */
    private $slack;

    /** @var Twitter|ObjectProphecy */
    private $twitter;

    public function setUp(): void
    {
        $this->twitter  = $this->prophesize(Twitter::class);
        $this->slack    = $this->prophesize(SlackClientInterface::class);
        $this->listener = new TwitterReplyListener(
            $this->twitter->reveal(),
            $this->slack->reveal()
        );
    }

    public function testSendsErrorResponseIfUpdateFails(): void
    {
        $reply     = new TwitterReply(
            'https://twitter.com/getlaminas/status/1239539812941651968',
            'This is the message',
            'http://localhost:9000/api/slack'
        );
        $exception = new RuntimeException('error');

        $this->twitter
            ->post(
                'statuses/update',
                [
                    'status'                => '@getlaminas This is the message',
                    'in_reply_to_status_id' => '1239539812941651968',
                    'username'              => '@getlaminas',
                ]
            )
            ->willThrow($exception)
            ->shouldBeCalled();

        $this->slack
            ->sendWebhookMessage(
                'http://localhost:9000/api/slack',
                Argument::that(function ($message) {
                    TestCase::assertInstanceOf(SlashResponseMessage::class, $message);
                    /** @var SlashResponseMessage $message */
                    TestCase::assertStringContainsString('Failed to send', $message->getText());
                    return $message;
                })
            )
            ->shouldBeCalled();

        $this->assertNull($this->listener->__invoke($reply));
    }

    public function testSendsSuccessNotificationIfUpdateSucceeds(): void
    {
        $reply     = new TwitterReply(
            'https://twitter.com/getlaminas/status/1239539812941651968',
            'This is the message',
            'http://localhost:9000/api/slack'
        );
        $exception = new RuntimeException('error');

        $this->twitter
            ->post(
                'statuses/update',
                [
                    'status'                => '@getlaminas This is the message',
                    'in_reply_to_status_id' => '1239539812941651968',
                    'username'              => '@getlaminas',
                ]
            )
            ->shouldBeCalled();

        $this->slack
            ->sendWebhookMessage(
                'http://localhost:9000/api/slack',
                Argument::that(function ($message) {
                    TestCase::assertInstanceOf(SlashResponseMessage::class, $message);
                    /** @var SlashResponseMessage $message */
                    TestCase::assertStringContainsString('reply sent', $message->getText());
                    return $message;
                })
            )
            ->shouldBeCalled();

        $this->assertNull($this->listener->__invoke($reply));
    }
}
