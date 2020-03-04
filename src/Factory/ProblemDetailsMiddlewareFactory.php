<?php

declare(strict_types=1);

namespace App\Factory;

use Mezzio\ProblemDetails\ProblemDetailsMiddleware;
use Mezzio\ProblemDetails\ProblemDetailsResponseFactory;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Throwable;

use function date;

class ProblemDetailsMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): ProblemDetailsMiddleware
    {
        $middleware = new ProblemDetailsMiddleware($container->get(ProblemDetailsResponseFactory::class));

        if ($container->has(LoggerInterface::class)) {
            $logger = $container->get(LoggerInterface::class);
            $middleware->attachListener(function (
                Throwable $throwable,
                RequestInterface $request,
                ResponseInterface $response
            ) use ($logger): void {
                $logger->error('"{method} {uri}": {message} in {file}:{line}', [
                    'date'    => date('Y-m-d H:i:s'),
                    'method'  => $request->getMethod(),
                    'uri'     => (string) $request->getUri(),
                    'message' => $throwable->getMessage(),
                    'file'    => $throwable->getFile(),
                    'line'    => $throwable->getLine(),
                ]);
            });
        }

        return $middleware;
    }
}
