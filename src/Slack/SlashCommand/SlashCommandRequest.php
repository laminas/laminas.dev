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
        $this->payload = $payload;
    }

    public function command(): string
    {
        return $this->command;
    }

    public function text(): string
    {
        return $this->payload['text'] ?? '';
    }

    public function userId(): string
    {
        return $this->payload['user_id'] ?? '';
    }

    public function responseUrl(): string
    {
        return $this->payload['response_url'] ?? '';
    }
}
