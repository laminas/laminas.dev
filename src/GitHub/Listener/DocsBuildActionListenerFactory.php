<?php

declare(strict_types=1);

namespace App\GitHub\Listener;

use GuzzleHttp\Client;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Log\LoggerInterface;

class DocsBuildActionListenerFactory
{
    public function __invoke(ContainerInterface $container): DocsBuildActionListener
    {
        return new DocsBuildActionListener(
            $container->get('config')['github']['token'],
            $container->get(Client::class),
            $container->get(RequestFactoryInterface::class),
            $container->get(LoggerInterface::class)
        );
    }
}
