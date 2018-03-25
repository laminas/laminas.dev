<?php

declare(strict_types=1);

namespace App\GitHub\Handler;

use App\Asana\Domain\Task;
use App\Asana\Service\AsanaService;
use App\GitHub\Message\GitHubIssue;
use function sprintf;

class GitHubIssueHandler
{
    /** @var AsanaService */
    private $asana;

    public function __construct(AsanaService $asana)
    {
        $this->asana = $asana;
    }

    public function __invoke(GitHubIssue $issue) : void
    {
        $task = $this->asana->findTask($issue->getCode());
        if ($issue->isAssignedTo('xtreamwayz') === false) {
            if ($task !== null) {
                // Task is not assigned to me, so delete it
                $this->asana->deleteTask($task);
            }

            return;
        }

        if ($task === null) {
            $task = Task::fromState([
                'name'  => '',
                'notes' => '',
            ]);
        }

        $task->update(
            sprintf('%s %s', $issue->getCode(), $issue->getTitle()),
            sprintf("%s\n\n%s", $issue->getUrl(), $issue->getBody()),
            $issue->isClosed()
        );

        $this->asana->saveTask($task);
    }
}
