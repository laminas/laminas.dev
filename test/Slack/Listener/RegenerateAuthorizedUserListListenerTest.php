<?php

declare(strict_types=1);

namespace AppTest\Slack\Listener;

use App\Slack\Domain\SlashResponseMessage;
use App\Slack\Event\RegenerateAuthorizedUserList;
use App\Slack\Listener\RegenerateAuthorizedUserListListener;
use App\Slack\Response\SlackResponseInterface;
use App\Slack\SlackClientInterface;
use App\Slack\SlashCommand\AuthorizedUserListInterface;
use DomainException;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;

class RegenerateAuthorizedUserListListenerTest extends TestCase
{
    /** @var AuthorizedUserListInterface|ObjectProphecy */
    private $acl;

    /** @var RegenerateAuthorizedUserListListener */
    private $listener;

    /** @var SlackClientInterface|ObjectProphecy */
    private $slack;

    public function setUp(): void
    {
        $this->acl   = $this->prophesize(AuthorizedUserListInterface::class);
        $this->slack = $this->prophesize(SlackClientInterface::class);

        $this->listener = new RegenerateAuthorizedUserListListener(
            $this->acl->reveal(),
            $this->slack->reveal()
        );
    }

    public function testReportsErrorsToSlack(): void
    {
        $request  = new RegenerateAuthorizedUserList('webhook-response-url');
        $response = $this->prophesize(SlackResponseInterface::class)->reveal();

        $this->acl->build()->willThrow(new DomainException('message'))->shouldBeCalled();

        $this->slack
            ->sendWebhookMessage(
                'webhook-response-url',
                Argument::that(function ($message) {
                    TestCase::assertInstanceOf(SlashResponseMessage::class, $message);
                    TestCase::assertStringContainsString('Error rebuilding', $message->getText());

                    return $message;
                })
            )
            ->willReturn($response)
            ->shouldBeCalled();

        $this->assertNull($this->listener->__invoke($request));
    }

    public function testReportsQueueRequestToSlack(): void
    {
        $request  = new RegenerateAuthorizedUserList('webhook-response-url');
        $response = $this->prophesize(SlackResponseInterface::class)->reveal();

        $this->acl->build()->shouldBeCalled();

        $this->slack
            ->sendWebhookMessage(
                'webhook-response-url',
                Argument::that(function ($message) {
                    TestCase::assertInstanceOf(SlashResponseMessage::class, $message);
                    TestCase::assertStringContainsString('Queueing request', $message->getText());

                    return $message;
                })
            )
            ->willReturn($response)
            ->shouldBeCalled();

        $this->assertNull($this->listener->__invoke($request));
    }
}
