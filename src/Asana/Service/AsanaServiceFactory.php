<?php

declare(strict_types=1);

namespace App\Asana\Service;

use Asana\Client;
use Psr\Container\ContainerInterface;
use RuntimeException;

class AsanaServiceFactory
{
    public function __invoke(ContainerInterface $container) : AsanaService
    {
        $config = $container->has('config') ? $container->get('config') : [];

        if (! isset($config['asana']['workspace'])) {
            throw new RuntimeException('Missing the workspace in the Slack configuration');
        }

        if (! isset($config['asana']['project'])) {
            throw new RuntimeException('Missing the project in the Slack configuration');
        }

        return new AsanaService(
            $container->get(Client::class),
            $config['asana']['workspace'],
            $config['asana']['project']
        );
    }
}
