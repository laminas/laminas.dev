<?php

declare(strict_types=1);

namespace App\Factory;

use App\HttpClient;
use GuzzleHttp\Client as Guzzle;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\RequestFactoryInterface;

class HttpClientFactory
{
    public function __invoke(ContainerInterface $container): HttpClient
    {
        return new HttpClient(
            new Guzzle(),
            $container->get(RequestFactoryInterface::class)
        );
    }
}
