<?php

declare(strict_types=1);

namespace App\Asana;

use Asana\Client;
use Psr\Container\ContainerInterface;
use RuntimeException;

class AsanaClientFactory
{
    public function __invoke(ContainerInterface $container) : Client
    {
        $config = $container->has('config') ? $container->get('config') : [];

        if (! isset($config['asana']['token'])) {
            throw new RuntimeException('Missing a token in the Asana configuration');
        }

        return Client::accessToken($config['asana']['token']);
    }
}
