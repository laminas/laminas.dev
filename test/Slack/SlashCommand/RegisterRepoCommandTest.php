<?php

declare(strict_types=1);

namespace AppTest\Slack\SlashCommand;

use App\GitHub\Event\RegisterWebhook;
use App\Slack\SlashCommand\RegisterRepoCommand;
use App\Slack\SlashCommand\SlashCommandRequest;
use App\Slack\SlashCommand\SlashCommandResponseFactory;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\EventDispatcher\EventDispatcherInterface;

class RegisterRepoCommandTest extends TestCase
{
    public function testDispatchesRegisterWebhookWithRequestDataAndReturnsNull(): void
    {
        $repo        = 'laminas/laminas-repo-of-some-sort';
        $responseUrl = 'https://hooks.slack.com/services/T00000000/B00000000/XXXXXXXXXXXXXXXXXXXXXXXX';

        $request = $this->prophesize(SlashCommandRequest::class);
        $request->text()->willReturn($repo)->shouldBeCalled();
        $request->responseUrl()->willReturn($responseUrl)->shouldBeCalled();

        $dispatcher = $this->prophesize(EventDispatcherInterface::class);
        $dispatcher
            ->dispatch(Argument::that(function (RegisterWebhook $event) use ($repo, $responseUrl): RegisterWebhook {
                TestCase::assertSame($repo, $event->repo());
                TestCase::assertSame($responseUrl, $event->responseUrl());
                return $event;
            }))
            ->shouldBeCalled();

        $responseFactory = $this->prophesize(SlashCommandResponseFactory::class);
        $responseFactory
            ->createResponse(Argument::any())
            ->shouldNotBeCalled();

        $command = new RegisterRepoCommand(
            $responseFactory->reveal(),
            $dispatcher->reveal()
        );

        $this->assertNull($command->dispatch($request->reveal()));
    }
}
