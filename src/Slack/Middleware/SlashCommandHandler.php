<?php

declare(strict_types=1);

namespace App\Slack\Middleware;

use App\Slack\SlashCommand\SlashCommandRequest;
use App\Slack\SlashCommand\SlashCommands;
use Laminas\Stdlib\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SlashCommandHandler implements RequestHandlerInterface
{
    /** @var SlashCommands */
    private $commands;

    public function __construct(SlashCommands $commands)
    {
        $this->commands = $commands;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->commands->handle(new SlashCommandRequest($request->getParsedBody()));
    }
}
