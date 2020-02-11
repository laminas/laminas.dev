<?php

declare(strict_types=1);

namespace App;

use Phly\EventDispatcher\ListenerProvider\AttachableListenerProvider;
use Phly\Swoole\TaskWorker\DeferredListenerDelegator;
use Psr\EventDispatcher\ListenerProviderInterface;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
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
                GitHub\Handler\GitHubIssueHandler::class       => [DeferredListenerDelegator::class],
                GitHub\Handler\GitHubPullRequestHandler::class => [DeferredListenerDelegator::class],
                GitHub\Handler\GitHubPushHandler::class        => [DeferredListenerDelegator::class],
                GitHub\Handler\GitHubStatusHandler::class      => [DeferredListenerDelegator::class],
                Slack\Message\DeployMessageHandler::class      => [DeferredListenerDelegator::class],
            ],
            'factories' => [
                GitHub\Handler\GitHubIssueHandler::class       => GitHub\Handler\GitHubIssueHandlerFactory::class,
                GitHub\Handler\GitHubPullRequestHandler::class => GitHub\Handler\GitHubPullRequestHandlerFactory::class,
                GitHub\Handler\GitHubPushHandler::class        => GitHub\Handler\GitHubPushHandlerFactory::class,
                GitHub\Handler\GitHubStatusHandler::class      => GitHub\Handler\GitHubStatusHandlerFactory::class,
                Slack\Message\DeployMessageHandler::class      => Slack\Message\DeployMessageHandlerFactory::class,
            ],
        ];
    }
}
