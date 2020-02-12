<?php

declare(strict_types=1);

namespace App;

use Laminas\Http\Client\Adapter\Curl;
use Laminas\Twitter\Twitter as TwitterClient;
use Phly\EventDispatcher\ListenerProvider\AttachableListenerProvider;
use Phly\Swoole\TaskWorker\DeferredListenerDelegator;
use Psr\EventDispatcher\ListenerProviderInterface;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
            'getlaminas' => [
                'token' => '',
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
                GitHub\Handler\GitHubIssueHandler::class                => GitHub\Handler\GitHubIssueHandlerFactory::class,
                GitHub\Handler\GitHubPullRequestHandler::class          => GitHub\Handler\GitHubPullRequestHandlerFactory::class,
                GitHub\Handler\GitHubPushHandler::class                 => GitHub\Handler\GitHubPushHandlerFactory::class,
                GitHub\Handler\GitHubReleaseTweetHandler::class         => GitHub\Handler\GitHubReleaseTweetHandlerFactory::class,
                GitHub\Handler\GitHubReleaseWebsiteUpdateHandler::class => GitHub\Handler\GitHubReleaseWebsiteUpdateHandlerFactory::class,
                GitHub\Handler\GitHubStatusHandler::class               => GitHub\Handler\GitHubStatusHandlerFactory::class,
                Slack\Message\DeployMessageHandler::class               => Slack\Message\DeployMessageHandlerFactory::class,
                TwitterClient::class                                    => Twitter\TwitterClientFactory::class,
            ],
        ];
    }
}
