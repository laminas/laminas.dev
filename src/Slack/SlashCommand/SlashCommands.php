<?php

declare(strict_types=1);

namespace App\Slack\SlashCommand;

use Psr\Http\Message\ResponseInterface;

class SlashCommands
{
    /** AuthorizedUserListInterface */
    private $authorizedUsers;

    /** @var SlashCommandInterface[] array<string, SlashCommandInterface> */
    private $commands = [];

    /** @var SlashCommandResponseFactory */
    private $responseFactory;

    public function __construct(
        SlashCommandResponseFactory $responseFactory,
        AuthorizedUserListInterface $authorizedUsers
    ) {
        $this->responseFactory = $responseFactory;
        $this->authorizedUsers = $authorizedUsers;
    }

    public function attach(SlashCommandInterface $command): void
    {
        $name = strtolower($command->command());
        $this->commands[$name] = $command;
    }

    /**
     * @return null|string Returns null on success, and a string indicating the
     *     error message on failure.
     */
    public function handle(SlashCommandRequest $request): ResponseInterface
    {
        $command = strtolower($request->command());

        // Handle the /laminas command
        if ($command === 'laminas') {
            return $this->responseFactory->createResponse(sprintf(
                "Available commands:\n\n%s",
                $this->help()
            ), 200);
        }

        // Unknown command; detail available slash commands
        if (! isset($this->commands[$command])) {
            return $this->responseFactory->createResponse(sprintf(
                "Unknown command '%s'; available commands:\n\n%s",
                $command,
                $this->help()
            ), 200);
        }

        $command = $this->commands[$command];
        $payload = $request->text();

        // Request was for help with a command; return that
        if (preg_match('/^help\s/i', $payload)) {
            return $this->responseFactory->createResponse($command->help(), 200);
        }

        $response = $command->validate($request, $this->authorizedUsers);

        // Was the payload malformed? Inform the user.
        if ($response !== null) {
            return $response;
        }

        // Dispatch the command with the request
        return $command->dispatch($request);
    }

    private function help(): string
    {
        $help = array_merge(
            ['- */laminas:* list commands this bot provides'],
            array_map(function (SlashCommandInterface $command) {
                $usage = $command->usage();
                return sprintf(
                    '- */%s%s:* %s',
                    $command->command(),
                    empty($usage) ? '' : ' ' . $usage,
                    $command->help()
                );
            }, $this->commands)
        );
        return implode("\n", $help);
    }
}
