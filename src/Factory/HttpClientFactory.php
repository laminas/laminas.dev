<?php

declare(strict_types=1);

namespace App\Factory;

use App\HttpClient;
use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\RequestFactoryInterface;

use function GuzzleHttp\choose_handler;

class HttpClientFactory
{
    public function __invoke(ContainerInterface $container): HttpClient
    {
        $stack = new HandlerStack();
        $stack->setHandler(choose_handler());
        $stack->push(Middleware::redirect(), 'allow_redirects');
        $stack->push(Middleware::prepareBody(), 'prepare_body');

        return new HttpClient(
            new Guzzle(['handler' => $stack]),
            $container->get(RequestFactoryInterface::class)
        );
    }
}
