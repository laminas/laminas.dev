<?php

declare(strict_types=1);

namespace App\GitHub;

use Phly\EventDispatcher\ListenerProvider\AttachableListenerProvider;
use Psr\Container\ContainerInterface;

class ListenerProviderDelegatorFactory
{
    public function __invoke(ContainerInterface $container, string $name, callable $factory): AttachableListenerProvider
    {
        /** @var AttachableListenerProvider $provider */
        $provider = $factory();

        // phpcs:disable
        $provider->listen(Event\DocsBuildAction::class,     $container->get(Listener\DocsBuildActionListener::class));
        $provider->listen(Event\GitHubIssue::class,         $container->get(Listener\GitHubIssueListener::class));
        $provider->listen(Event\GitHubIssueComment::class,  $container->get(Listener\GitHubIssueCommentListener::class));
        $provider->listen(Event\GitHubPullRequest::class,   $container->get(Listener\GitHubPullRequestListener::class));
        $provider->listen(Event\GitHubRelease::class,       $container->get(Listener\GitHubReleaseSlackListener::class));
        $provider->listen(Event\GitHubRelease::class,       $container->get(Listener\GitHubReleaseWebsiteUpdateListener::class));
        $provider->listen(Event\GitHubRelease::class,       $container->get(Listener\GitHubReleaseMastodonListener::class));
        $provider->listen(Event\GitHubStatus::class,        $container->get(Listener\GitHubStatusListener::class));
        $provider->listen(Event\RegisterWebhook::class,     $container->get(Listener\RegisterWebhookListener::class));
        // phpcs:enable

        return $provider;
    }
}
