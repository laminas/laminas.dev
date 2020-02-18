<?php

declare(strict_types=1);

namespace App\Slack\SlashCommand;

use RuntimeException;

class SlashCommandRequest
{
    /** @var string */
    private $command;

    /** @var string */
    private $payload;

    public function __construct(array $payload)
    {
        if (! isset($payload['command'])) {
            throw new RuntimeException(
                'Slash command is missing "command" element',
                422
            );
        }

        $this->command = ltrim($payload['command'], '/');
        $this->payload = $payload['text'] ?? '';
    }

    public function command(): string
    {
        return $this->command;
    }

    public function payload(): string
    {
        return $this->payload;
    }
}
