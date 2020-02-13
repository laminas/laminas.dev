<?php

declare(strict_types=1);

namespace App;

use Laminas\Http\Client\Adapter\Curl;
use Laminas\Stratigility\Middleware\ErrorHandler;
use Laminas\Twitter\Twitter as TwitterClient;
use Mezzio\ProblemDetails\ProblemDetailsMiddleware;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Phly\EventDispatcher\EventDispatcher;
use Phly\EventDispatcher\ListenerProvider\AttachableListenerProvider;
use Phly\Swoole\TaskWorker\DeferredListenerDelegator;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\Log\LoggerInterface;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
            'getlaminas' => [
                'token' => '',
            ],
            'monolog' => [
                'handlers' => [
                    [
                        'type'   => StreamHandler::class,
                        'stream' => 'data/log/app-{date}.log',
                        'level'  => Logger::DEBUG,
                    ],
                ],
            ],
            'twitter' => [
                'access_token' => [
                    'token'  => '',
                    'secret' => '',
                ],
                'oauth_options' => [
                    'consumerKey'    => '',
                    'consumerSecret' => '',
                ],
                'http_client_options' => [
                    'adapter' => Curl::class,
                    'curloptions' => [
                        CURLOPT_SSL_VERIFYHOST => false,
                        CURLOPT_SSL_VERIFYPEER => false,
                    ],
                ],
            ],
        ];
    }

    public function getDependencies(): array
    {
        return [
            'aliases' => [
                EventDispatcherInterface::class  => EventDispatcher::class,
                ListenerProviderInterface::class => AttachableListenerProvider::class,
            ],
            'delegator_factories' => [
                AttachableListenerProvider::class => [
                    GitHub\ListenerProviderDelegatorFactory::class,
                    Slack\ListenerProviderDelegatorFactory::class,
                ],
                GitHub\Handler\GitHubIssueHandler::class                => [DeferredListenerDelegator::class],
                GitHub\Handler\GitHubPullRequestHandler::class          => [DeferredListenerDelegator::class],
                GitHub\Handler\GitHubPushHandler::class                 => [DeferredListenerDelegator::class],
                GitHub\Handler\GitHubReleaseTweetHandler::class         => [DeferredListenerDelegator::class],
                GitHub\Handler\GitHubReleaseWebsiteUpdateHandler::class => [DeferredListenerDelegator::class],
                GitHub\Handler\GitHubStatusHandler::class               => [DeferredListenerDelegator::class],
                Slack\Message\DeployMessageHandler::class               => [DeferredListenerDelegator::class],
            ],
            'factories' => [
                ErrorHandler::class                                     => Factory\ErrorHandlerFactory::class,
                GitHub\Handler\GitHubIssueHandler::class                => GitHub\Handler\GitHubIssueHandlerFactory::class,
                GitHub\Handler\GitHubPullRequestHandler::class          => GitHub\Handler\GitHubPullRequestHandlerFactory::class,
                GitHub\Handler\GitHubPushHandler::class                 => GitHub\Handler\GitHubPushHandlerFactory::class,
                GitHub\Handler\GitHubReleaseTweetHandler::class         => GitHub\Handler\GitHubReleaseTweetHandlerFactory::class,
                GitHub\Handler\GitHubReleaseWebsiteUpdateHandler::class => GitHub\Handler\GitHubReleaseWebsiteUpdateHandlerFactory::class,
                GitHub\Handler\GitHubStatusHandler::class               => GitHub\Handler\GitHubStatusHandlerFactory::class,
                GitHub\Middleware\GithubRequestHandler::class           => GitHub\Middleware\GithubRequestHandlerFactory::class,
                GitHub\Middleware\VerificationMiddleware::class         => GitHub\Middleware\VerificationMiddlewareFactory::class,
                Handler\HomePageHandler::class                          => Handler\HomePageHandlerFactory::class,
                LoggerInterface::class                                  => Factory\LoggerFactory::class,
                ProblemDetailsMiddleware::class                         => Factory\ProblemDetailsMiddlewareFactory::class,
                Slack\Message\DeployMessageHandler::class               => Slack\Message\DeployMessageHandlerFactory::class,
                Slack\Middleware\VerificationMiddleware::class          => Slack\Middleware\VerificationMiddlewareFactory::class,
                Slack\Middleware\DeployHandler::class                   => Slack\Middleware\DeployHandlerFactory::class,
                Slack\SlackClientInterface::class                       => Slack\SlackClientFactory::class,
                TwitterClient::class                                    => Factory\TwitterClientFactory::class,
            ],
        ];
    }
}
