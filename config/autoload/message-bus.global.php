<?php

declare(strict_types=1);

namespace App;

return [
    // @codingStandardsIgnoreStart
    'dependencies' => [
        'factories' => [
            //'messenger.transport.xtreamlabs' => [EnqueueTransportFactory::class, 'redis:'],

            GitHub\Handler\GitHubIssueHandler::class       => GitHub\Handler\GitHubIssueHandlerFactory::class,
            GitHub\Handler\GitHubPullRequestHandler::class => GitHub\Handler\GitHubPullRequestHandlerFactory::class,
            GitHub\Handler\GitHubPushHandler::class        => GitHub\Handler\GitHubPushHandlerFactory::class,
            GitHub\Handler\GitHubStatusHandler::class      => GitHub\Handler\GitHubStatusHandlerFactory::class,
            Slack\Message\DeployMessageHandler::class      => Slack\Message\DeployMessageHandlerFactory::class,
        ],
    ],
    // @codingStandardsIgnoreEnd

    'messenger' => [
        'default_bus'        => 'messenger.bus.command',
        'default_middleware' => true,
        'buses'              => [
            'messenger.bus.command' => [
                'handlers'   => [
                    GitHub\Message\GitHubIssue::class       => GitHub\Handler\GitHubIssueHandler::class,
                    GitHub\Message\GitHubPullRequest::class => GitHub\Handler\GitHubPullRequestHandler::class,
                    GitHub\Message\GitHubPush::class        => GitHub\Handler\GitHubPushHandler::class,
                    GitHub\Message\GitHubStatus::class      => GitHub\Handler\GitHubStatusHandler::class,
                    Slack\Message\DeployMessage::class      => Slack\Message\DeployMessageHandler::class,
                ],
                'middleware' => [],
                'routes'     => [
                    GitHub\Message\GitHubIssue::class       => 'messenger.transport.xtreamlabs',
                    GitHub\Message\GitHubPullRequest::class => 'messenger.transport.xtreamlabs',
                    GitHub\Message\GitHubPush::class        => 'messenger.transport.xtreamlabs',
                    GitHub\Message\GitHubStatus::class      => 'messenger.transport.xtreamlabs',
                    Slack\Message\DeployMessage::class      => 'messenger.transport.xtreamlabs',
                ],
            ],

            'messenger.bus.event' => [
                'handlers'   => [],
                'middleware' => [],
                'routes'     => [],
            ],

            'messenger.bus.query' => [
                'handlers'   => [],
                'middleware' => [],
                'routes'     => [],
            ],
        ],
    ],
];
