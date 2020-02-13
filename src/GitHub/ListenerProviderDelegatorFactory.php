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

        $provider->listen(Event\GitHubIssue::class, $container->get(Listener\GitHubIssueListener::class));
        $provider->listen(Event\GitHubPullRequest::class, $container->get(Listener\GitHubPullRequestListener::class));
        $provider->listen(Event\GitHubPush::class, $container->get(Listener\GitHubPushListener::class));
        $provider->listen(Event\GitHubRelease::class, $container->get(Listener\GitHubReleaseWebsiteUpdateListener::class));
        $provider->listen(Event\GitHubRelease::class, $container->get(Listener\GitHubReleaseTweetListener::class));
        $provider->listen(Event\GitHubStatus::class, $container->get(Listener\GitHubStatusListener::class));

        return $provider;
    }
}
