<?php

declare(strict_types=1);

namespace App\Slack\SlashCommand;

use Laminas\Feed\Reader\Http\ResponseInterface;

class SlashCommands
{
    /** @var SlashCommandInterface[] array<string, SlashCommandInterface> */
    private $commands = [];

    /** @var SlashCommandResponseFactory */
    private $responseFactory;

    public function __construct(SlashCommandResponseFactory $responseFactory)
    {
        $this->responseFactory = $responseFactory;
    }

    public function attach(SlashCommandInterface $command): void
    {
        $this->commands[$command->command()] = $command;
    }

    /**
     * @return null|string Returns null on success, and a string indicating the
     *     error message on failure.
     */
    public function handle(SlashCommandRequest $request): ResponseInterface
    {
        $command = $request->command();
        if (! isset($this->commands[$command])) {
            return $this->responseFactory->createResponse(sprintf(
                "Unknown command '%s'; available commands:\n\n%s",
                $command,
                $this->help()
            ), 400);
        }

        $command = $this->commands[$command];
        $payload = $request->payload();
        $message = $command->validate($payload);

        if ($message !== null) {
            return $this->responseFactory->createResponse($message, 422);
        }

        return $command->dispatch($payload);
    }

    private function help(): string
    {
        $help = array_reduce($this->commands, function ($help, SlashCommandInterface $command) {
            return sprintf("%s\n%s", $help, $command->help());
        }, '');
        return trim($help);
    }
}
