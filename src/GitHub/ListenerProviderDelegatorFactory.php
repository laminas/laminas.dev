<?php

declare(strict_types=1);

namespace App\GitHub;

use Phly\EventDispatcher\ListenerProvider\AttachableListenerProvider;
use Psr\Container\ContainerInterface;

class ListenerProviderDelegatorFactory
{
    public function __invoke(ContainerInterface $container, string $name, callable $factory): AttachableListenerProvider
    {
        $provider = $factory();

        $provider->listen(Message\GitHubIssue::class, $container->get(Handler\GitHubIssueHandler::class));
        $provider->listen(Message\GitHubPullRequest::class, $container->get(Handler\GitHubPullRequestHandler::class));
        $provider->listen(Message\GitHubPush::class, $container->get(Handler\GitHubPushHandler::class));
        $provider->listen(Message\GitHubRelease::class, $container->get(Handler\GitHubReleaseWebsiteUpdateHandler::class));
        $provider->listen(Message\GitHubRelease::class, $container->get(Handler\GitHubReleaseTweetHandler::class));
        $provider->listen(Message\GitHubStatus::class, $container->get(Handler\GitHubStatusHandler::class));

        return $provider;
    }
}
