<?php

declare(strict_types=1);

namespace AppTest\Slack\SlashCommand;

use App\Slack\SlashCommand\AuthorizedUserListInterface;
use App\Slack\SlashCommand\SlashCommandInterface;
use App\Slack\SlashCommand\SlashCommandRequest;
use App\Slack\SlashCommand\SlashCommandResponseFactory;
use App\Slack\SlashCommand\SlashCommands;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ResponseInterface;

class SlashCommandsTest extends TestCase
{
    /** @var AuthorizedUserListInterface|ObjectProphecy */
    private $authorizedUsers;

    /** @var SlashCommands */
    private $commands;

    /** @var ResponseInterface */
    private $response;

    /** @var SlashCommandResponseFactory|ObjectProphecy */
    private $responseFactory;

    public function setUp(): void
    {
        $this->authorizedUsers = $this->prophesize(AuthorizedUserListInterface::class);
        $this->responseFactory = $this->prophesize(SlashCommandResponseFactory::class);
        $this->response        = $this->prophesize(ResponseInterface::class)->reveal();

        $this->commands = new SlashCommands(
            $this->responseFactory->reveal(),
            $this->authorizedUsers->reveal()
        );
    }

    public function testHandleReturnsHelpResponseIfLaminasCommandRequested(): void
    {
        $request = $this->prophesize(SlashCommandRequest::class);
        $request->command()->willReturn('laminas')->shouldBeCalled();

        $this->responseFactory
            ->createResponse(
                Argument::that(function ($message) {
                     TestCase::assertRegExp('#^Available commands.*?- \*/laminas:\* list commands#s', $message);
                     return $message;
                }),
                200
            )
            ->willReturn($this->response)
            ->shouldBeCalled();

        $this->assertSame(
            $this->response,
            $this->commands->handle($request->reveal())
        );
    }

    public function testHandleReturnsHelpResponseIfCommandIsUnknown(): void
    {
        $request = $this->prophesize(SlashCommandRequest::class);
        $request->command()->willReturn('unknown-command')->shouldBeCalled();

        // phpcs:disable
        $this->responseFactory
            ->createResponse(
                Argument::that(function ($message) {
                     TestCase::assertRegExp('#^Unknown command \'unknown-command\'; available commands:.*?- \*/laminas:\* list commands#s', $message);
                     return $message;
                }),
                200
            )
            ->willReturn($this->response)
            ->shouldBeCalled();
        // phpcs:enable

        $this->assertSame(
            $this->response,
            $this->commands->handle($request->reveal())
        );
    }

    public function testHandleReturnsResponseReturnedWhenValidatingCommand(): void
    {
        $request = $this->prophesize(SlashCommandRequest::class);
        $request->command()->willReturn('requested-command')->shouldBeCalled();
        $request->text()->willReturn('')->shouldBeCalled();

        /** @var SlashCommandInterface $command */
        $command = $this->prophesize(SlashCommandInterface::class);
        $command->command()->willReturn('requested-command')->shouldBeCalled();
        $command
            ->validate(
                Argument::that([$request, 'reveal']),
                Argument::that([$this->authorizedUsers, 'reveal'])
            )
            ->willReturn($this->response)
            ->shouldBeCalled();
        $command->dispatch(Argument::that([$request, 'reveal']))->shouldNotBeCalled();

        $this->responseFactory
             ->createResponse(Argument::any(), Argument::any())
             ->shouldNotBeCalled();

        $this->commands->attach($command->reveal());

        $this->assertSame(
            $this->response,
            $this->commands->handle($request->reveal())
        );
    }

    public function testHandleReturnsResponseFromCommandIfValidAndNotRequestingHelp(): void
    {
        $request = $this->prophesize(SlashCommandRequest::class);
        $request->command()->willReturn('requested-command')->shouldBeCalled();
        $request->text()->willReturn('')->shouldBeCalled();

        /** @var SlashCommandInterface $command */
        $command = $this->prophesize(SlashCommandInterface::class);
        $command->command()->willReturn('requested-command')->shouldBeCalled();
        $command
            ->validate(
                Argument::that([$request, 'reveal']),
                Argument::that([$this->authorizedUsers, 'reveal'])
            )
            ->willReturn(null)
            ->shouldBeCalled();
        $command
            ->dispatch(Argument::that([$request, 'reveal']))
            ->willReturn($this->response)
            ->shouldBeCalled();

        $this->responseFactory
             ->createResponse(Argument::any(), Argument::any())
             ->shouldNotBeCalled();

        $this->commands->attach($command->reveal());

        $this->assertSame(
            $this->response,
            $this->commands->handle($request->reveal())
        );
    }

    public function testHandleReturnsCommandHelpWhenRequested(): void
    {
        $helpMessage  = 'this is the help message';
        $usageMessage = '';

        $request = $this->prophesize(SlashCommandRequest::class);
        $request->command()->willReturn('requested-command')->shouldBeCalled();
        $request->text()->willReturn('help')->shouldBeCalled();

        /** @var SlashCommandInterface $command */
        $command = $this->prophesize(SlashCommandInterface::class);
        $command->command()->willReturn('requested-command')->shouldBeCalled();
        $command->usage()->willReturn($usageMessage)->shouldBeCalled();
        $command->help()->willReturn($helpMessage);
        $command
            ->validate(
                Argument::any(),
                Argument::any()
            )
            ->shouldNotBeCalled();
        $command
            ->dispatch(Argument::any())
            ->shouldNotBeCalled();

        $this->responseFactory
             ->createResponse(
                 Argument::that(function ($message) use ($helpMessage) {
                     TestCase::assertRegExp('#- \*/requested-command:\*#', $message);
                     TestCase::assertStringContainsString($helpMessage, $message);
                     return $message;
                 }),
                 200
             )
             ->willReturn($this->response)
             ->shouldBeCalled();

        $this->commands->attach($command->reveal());

        $this->assertSame(
            $this->response,
            $this->commands->handle($request->reveal())
        );
    }

    public function testHelpMessageIncludesHelpFromAllCommands(): void
    {
        $helpMessage1 = 'this is help message 1';
        $helpMessage2 = 'this is help message 2';

        $usage1 = '{repo}';
        $usage2 = '{user}';

        $request = $this->prophesize(SlashCommandRequest::class);
        $request->command()->willReturn('laminas')->shouldBeCalled();

        /** @var SlashCommandInterface $command */
        $command1 = $this->prophesize(SlashCommandInterface::class);
        $command1->command()->willReturn('command1')->shouldBeCalled();
        $command1->help()->willReturn($helpMessage1);
        $command1->usage()->willReturn($usage1);
        $command1->validate(Argument::any(), Argument::any())->shouldNotBeCalled();
        $command1->dispatch(Argument::any())->shouldNotBeCalled();

        $command2 = $this->prophesize(SlashCommandInterface::class);
        $command2->command()->willReturn('command2')->shouldBeCalled();
        $command2->help()->willReturn($helpMessage2);
        $command2->usage()->willReturn($usage2);
        $command2->validate(Argument::any(), Argument::any())->shouldNotBeCalled();
        $command2->dispatch(Argument::any())->shouldNotBeCalled();

        $this->responseFactory
             ->createResponse(
                 Argument::that(function ($message) use ($helpMessage1, $helpMessage2, $usage1, $usage2) {
                     TestCase::assertStringContainsString('/command1', $message);
                     TestCase::assertStringContainsString('/command2', $message);
                     TestCase::assertStringContainsString($helpMessage1, $message);
                     TestCase::assertStringContainsString($helpMessage2, $message);
                     TestCase::assertStringContainsString($usage1, $message);
                     TestCase::assertStringContainsString($usage2, $message);

                     return $message;
                 }),
                 200
             )
             ->willReturn($this->response)
             ->shouldBeCalled();

        $this->commands->attach($command1->reveal());
        $this->commands->attach($command2->reveal());

        $this->assertSame(
            $this->response,
            $this->commands->handle($request->reveal())
        );
    }
}
