<?php

declare(strict_types=1);

namespace App\Slack;

use Mezzio\Application;
use Psr\Container\ContainerInterface;

class ApplicationDelegatorFactory
{
    public function __invoke(ContainerInterface $container, string $name, callable $factory): Application
    {
        // Initialize the authorized user list
        $container->get(SlashCommand\AuthorizedUserListInterface::class);
        return $factory();
    }
}
