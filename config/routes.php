<?php

declare(strict_types=1);

use App\GitHub\Middleware\GithubRequestHandler;
use App\GitHub\Middleware\VerificationMiddleware as GithubVerificationMiddleware;
use App\Slack\Handler\DeployHandler as SlackDeployHandler;
use App\Slack\Middleware\VerificationMiddleware as SlackVerificationMiddleware;
use Psr\Container\ContainerInterface;
use Mezzio\Application;
use Mezzio\Helper\BodyParams\BodyParamsMiddleware;
use Mezzio\MiddlewareFactory;
use Mezzio\ProblemDetails\ProblemDetailsMiddleware;

/**
 * Setup routes with a single request method:
 *
 * $app->get('/', App\Handler\HomePageHandler::class, 'home');
 * $app->post('/album', App\Handler\AlbumCreateHandler::class, 'album.create');
 * $app->put('/album/:id', App\Handler\AlbumUpdateHandler::class, 'album.put');
 * $app->patch('/album/:id', App\Handler\AlbumUpdateHandler::class, 'album.patch');
 * $app->delete('/album/:id', App\Handler\AlbumDeleteHandler::class, 'album.delete');
 *
 * Or with multiple request methods:
 *
 * $app->route('/contact', App\Handler\ContactHandler::class, ['GET', 'POST', ...], 'contact');
 *
 * Or handling all request methods:
 *
 * $app->route('/contact', App\Handler\ContactHandler::class)->setName('contact');
 *
 * or:
 *
 * $app->route(
 *     '/contact',
 *     App\Handler\ContactHandler::class,
 *     Mezzio\Router\Route::HTTP_METHOD_ANY,
 *     'contact'
 * );
 */
return function (Application $app, MiddlewareFactory $factory, ContainerInterface $container): void {
    $app->get('/', App\Handler\HomePageHandler::class, 'home');
    $app->post('/api/github', [
        ProblemDetailsMiddleware::class,
        GitHubVerificationMiddleware::class,
        BodyParamsMiddleware::class,
        GithubRequestHandler::class,
    ], 'api.github');

    $app->post('/api/slack/deploy', [
        ProblemDetailsMiddleware::class,
        SlackVerificationMiddleware::class,
        BodyParamsMiddleware::class,
        SlackDeployHandler::class,
    ], 'api.slack');
};
