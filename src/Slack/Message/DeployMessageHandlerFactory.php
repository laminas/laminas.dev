<?php

declare(strict_types=1);

namespace App\Slack\Message;

use App\Slack\SlackClientInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

class DeployMessageHandlerFactory
{
    public function __invoke(ContainerInterface $container) : DeployMessageHandler
    {
        $config = $container->has('config') ? $container->get('config') : [];
        if (! isset($config['deployment'])) {
            throw new RuntimeException('Missing deployment configuration');
        }

        return new DeployMessageHandler(
            $container->get(SlackClientInterface::class),
            $container->get(LoggerInterface::class),
            $config['deployment']
        );
    }
}
