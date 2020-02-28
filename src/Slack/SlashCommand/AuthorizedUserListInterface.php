<?php

declare(strict_types=1);

namespace App\Slack\SlashCommand;

interface AuthorizedUserListInterface
{
    public function isAuthorized(string $userId): bool;

    public function build(): void;
}
