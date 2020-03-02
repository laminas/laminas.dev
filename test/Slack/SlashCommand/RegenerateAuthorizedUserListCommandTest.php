<?php

declare(strict_types=1);

namespace AppTest\Slack\SlashCommand;

use App\Slack\Event\RegenerateAuthorizedUserList;
use App\Slack\SlashCommand\RegenerateAuthorizedUserListCommand;
use App\Slack\SlashCommand\SlashCommandRequest;
use App\Slack\SlashCommand\SlashCommandResponseFactory;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;

class RegenerateAuthorizedUserListCommandTest extends TestCase
{
    public function testDispatchesRegenerateAuthorizedUserListWithRequestDataAndReturnsResponse(): void
    {
        $responseUrl = 'https://hooks.slack.com/services/T00000000/B00000000/XXXXXXXXXXXXXXXXXXXXXXXX';
        $response    = $this->prophesize(ResponseInterface::class)->reveal();

        $request = $this->prophesize(SlashCommandRequest::class);
        $request->text()->shouldNotBeCalled();
        $request->responseUrl()->willReturn($responseUrl)->shouldBeCalled();

        $dispatcher = $this->prophesize(EventDispatcherInterface::class);
        // phpcs:disable
        $dispatcher
            ->dispatch(Argument::that(function (RegenerateAuthorizedUserList $event) use ($responseUrl): RegenerateAuthorizedUserList {
                TestCase::assertSame($responseUrl, $event->responseUrl());
                return $event;
            }))
            ->shouldBeCalled();
        // phpcs:enable

        $responseFactory = $this->prophesize(SlashCommandResponseFactory::class);
        $responseFactory
            ->createResponse('Triggered rebuild of authorized user list')
            ->willReturn($response)
            ->shouldBeCalled();

        $command = new RegenerateAuthorizedUserListCommand(
            $responseFactory->reveal(),
            $dispatcher->reveal()
        );

        $this->assertSame($response, $command->dispatch($request->reveal()));
    }
}
