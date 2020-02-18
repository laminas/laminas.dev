<?php

declare(strict_types=1);

namespace App\Slack\SlashCommand;

use Laminas\Feed\Reader\Http\ResponseInterface;

interface SlashCommandInterface
{
    /**
     * Returns the name of the slash command the instance handles
     */
    public function command(): string;

    /**
     * Provide help usage for the command.
     */
    public function help(): string;

    /**
     * @return null|string Returns null if valid, and a string error message otherwise.
     */
    public function validate(string $payload): ?string;

    public function dispatch(string $payload): ResponseInterface;
}
