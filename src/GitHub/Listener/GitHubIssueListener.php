<?php

declare(strict_types=1);

namespace App\GitHub\Listener;

use App\GitHub\Event\GitHubIssue;

class GitHubIssueListener
{
    public function __invoke(GitHubIssue $issue) : void
    {
    }
}
