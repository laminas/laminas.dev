<?php

declare(strict_types=1);

namespace AppTest\Slack\SlashCommand;

use App\GitHub\Event\DocsBuildAction;
use App\Slack\SlashCommand\BuildDocsCommand;
use App\Slack\SlashCommand\SlashCommandRequest;
use App\Slack\SlashCommand\SlashCommandResponseFactory;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;

class BuildDocsCommandTest extends TestCase
{
    public function testDispatchesDocsBuildActionWithRequestDataAndReturnsResponse(): void
    {
        $repo        = 'laminas/laminas-repo-of-some-sort';
        $responseUrl = 'https://hooks.slack.com/services/T00000000/B00000000/XXXXXXXXXXXXXXXXXXXXXXXX';
        $response    = $this->prophesize(ResponseInterface::class)->reveal();

        $request = $this->prophesize(SlashCommandRequest::class);
        $request->text()->willReturn($repo)->shouldBeCalled();
        $request->responseUrl()->willReturn($responseUrl)->shouldBeCalled();

        $dispatcher = $this->prophesize(EventDispatcherInterface::class);
        $dispatcher
            ->dispatch(Argument::that(function (DocsBuildAction $event) use ($repo, $responseUrl): DocsBuildAction {
                TestCase::assertSame($repo, $event->repo());
                TestCase::assertSame($responseUrl, $event->responseUrl());
                return $event;
            }))
            ->shouldBeCalled();

        $responseFactory = $this->prophesize(SlashCommandResponseFactory::class);
        $responseFactory
            ->createResponse(sprintf('Documentation build for %s queued', $repo))
            ->willReturn($response)
            ->shouldBeCalled();

        $command = new BuildDocsCommand(
            $responseFactory->reveal(),
            $dispatcher->reveal()
        );

        $this->assertSame($response, $command->dispatch($request->reveal()));
    }
}
