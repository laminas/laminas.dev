<?php

declare(strict_types=1);

namespace AppTest\Slack\Middleware;

use App\Slack\Middleware\SlashCommandHandler;
use App\Slack\SlashCommand\SlashCommandRequest;
use App\Slack\SlashCommand\SlashCommands;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class SlashCommandHandlerTest extends TestCase
{
    public function testProxiesToSlashCommandsHandler(): void
    {
        $payload = [
            'command'      => '/some-command',
            'text'         => 'text for command',
            'user_id'      => 'ZSAXF4R2',
            'response_url' => 'https://hooks.slack.com/commands/1234/5677',
        ];
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getParsedBody()->willReturn($payload)->shouldBeCalled();

        $response = $this->prophesize(ResponseInterface::class)->reveal();

        $commands = $this->prophesize(SlashCommands::class);
        $commands
            ->handle(Argument::that(function ($request) {
                TestCase::assertInstanceOf(SlashCommandRequest::class, $request);
                TestCase::assertSame('some-command', $request->command());
                TestCase::assertSame('text for command', $request->text());
                TestCase::assertSame('ZSAXF4R2', $request->userId());
                TestCase::assertSame('https://hooks.slack.com/commands/1234/5677', $request->responseUrl());
                return $request;
            }))
            ->willReturn($response)
            ->shouldBeCalled();

        $handler = new SlashCommandHandler($commands->reveal());

        $this->assertSame($response, $handler->handle($request->reveal()));
    }
}
