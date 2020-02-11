<?php

declare(strict_types=1);

namespace App;

use Mezzio\ProblemDetails\ProblemDetailsMiddleware;
use Laminas\Stratigility\Middleware\ErrorHandler;
use Phly\EventDispatcher\EventDispatcher;
use Psr\EventDispatcher\EventDispatcherInterface;

return [
    'dependencies' => [
        'aliases'   => [
            EventDispatcherInterface::class => EventDispatcher::class,
        ],
        'factories' => [
            ErrorHandler::class             => Container\ErrorHandlerFactory::class,
            ProblemDetailsMiddleware::class => Container\ProblemDetailsMiddlewareFactory::class,

            GitHub\Middleware\GithubRequestHandler::class   => GitHub\Middleware\GithubRequestHandlerFactory::class,
            GitHub\Middleware\VerificationMiddleware::class => GitHub\Middleware\VerificationMiddlewareFactory::class,

            Handler\HomePageHandler::class => Handler\HomePageHandlerFactory::class,

            Slack\SlackClientInterface::class              => Slack\SlackClientFactory::class,
            Slack\Middleware\VerificationMiddleware::class => Slack\Middleware\VerificationMiddlewareFactory::class,
            Slack\Middleware\DeployHandler::class          => Slack\Middleware\DeployHandlerFactory::class,
        ],
    ],
];
