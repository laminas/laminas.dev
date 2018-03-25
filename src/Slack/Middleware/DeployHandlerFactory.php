<?php

declare(strict_types=1);

namespace App\Slack\Middleware;

use Psr\Container\ContainerInterface;
use RuntimeException;

class DeployHandlerFactory
{
    public function __invoke(ContainerInterface $container) : DeployHandler
    {
        $config = $container->has('config') ? $container->get('config') : [];
        if (! isset($config['deployment']['projects'])) {
            throw new RuntimeException('Missing deployment projects configuration');
        }

        return new DeployHandler(
            $container->get('messenger.bus.command'),
            $config['deployment']['projects']
        );
    }
}
