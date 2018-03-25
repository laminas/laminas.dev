<?php

declare(strict_types=1);

namespace App\Container;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Throwable;
use Zend\Expressive\Middleware\ErrorResponseGenerator;
use Zend\Stratigility\Middleware\ErrorHandler;
use function date;

class ErrorHandlerFactory
{
    public function __invoke(ContainerInterface $container) : ErrorHandler
    {
        $generator = $container->has(ErrorResponseGenerator::class)
            ? $container->get(ErrorResponseGenerator::class)
            : null;

        $errorHandler = new ErrorHandler($container->get(ResponseInterface::class), $generator);

        if ($container->has(LoggerInterface::class)) {
            $logger = $container->get(LoggerInterface::class);
            $errorHandler->attachListener(function (
                Throwable $throwable,
                RequestInterface $request,
                ResponseInterface $response
            ) use ($logger) : void {
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

        return $errorHandler;
    }
}
