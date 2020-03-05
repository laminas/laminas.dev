<?php

declare(strict_types=1);

namespace App\Factory;

use Laminas\Twitter\Twitter;
use Psr\Container\ContainerInterface;

class TwitterClientFactory
{
    public function __invoke(ContainerInterface $container): Twitter
    {
        $config = $container->get('config')['twitter'];
        return new Twitter($config);
    }
}
