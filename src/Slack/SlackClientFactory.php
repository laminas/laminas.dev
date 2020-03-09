<?php

declare(strict_types=1);

namespace App\Slack;

use App\HttpClientInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

class SlackClientFactory
{
    public function __invoke(ContainerInterface $container): SlackClient
    {
        $config = $container->has('config') ? $container->get('config') : [];
        if (! isset($config['slack']['token'])) {
            throw new RuntimeException('Missing a token in the Slack configuration');
        }

        return new SlackClient(
            $container->get(HttpClientInterface::class),
            $config['slack']['token'],
            $container->get(LoggerInterface::class)
        );
    }
}
