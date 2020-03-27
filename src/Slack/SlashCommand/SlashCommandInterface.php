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
     * Returns the format for usage
     */
    public function usage(): string;

    /**
     * Provide narrative help for the command
     */
    public function help(): string;

    /**
     * @return null|ResponseInterface Returns null if valid, and a response
     *     describing the validation error otherwise.
     */
    public function validate(
        SlashCommandRequest $request,
        AuthorizedUserListInterface $authorizedUsers
    ): ?ResponseInterface;

    public function dispatch(SlashCommandRequest $request): ?ResponseInterface;
}
