<?php

declare(strict_types=1);

namespace App\Slack\SlashCommand;

use Psr\Http\Message\ResponseInterface;

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
     * @return null|ResponseInterface Returns null if valid, and a response
     *     describing the validation error otherwise.
     */
    public function validate(string $payload, AuthorizedUserList $authorizedUsers): ?ResponseInterface;

    public function dispatch(string $payload): ResponseInterface;
}
