<?php

declare(strict_types=1);

namespace App\Factory;

use GuzzleHttp\Client as HttpClient;
use Psr\Container\ContainerInterface;

class HttpClientFactory
{
    public function __invoke(ContainerInterface $container): HttpClient
    {
        return new HttpClient();
    }
}
