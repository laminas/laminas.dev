<?php

declare(strict_types=1);

namespace App\GitHub\Listener;

use App\Slack\SlackClient;
use Psr\Container\ContainerInterface;

class GitHubIssueCommentListenerFactory
{
    public function __invoke(ContainerInterface $container): GitHubIssueCommentListener
    {
        return new GitHubIssueCommentListener(
            $container->get('config')['slack']['channels']['github'],
            $container->get(SlackClient::class)
        );
    }
}
