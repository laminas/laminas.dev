<?php

declare(strict_types=1);

namespace App\GitHub\Handler;

use App\GitHub\Message\GitHubIssue;
use function sprintf;

class GitHubIssueHandler
{
    public function __invoke(GitHubIssue $issue) : void
    {
    }
}
