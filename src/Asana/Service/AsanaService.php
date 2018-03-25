<?php

declare(strict_types=1);

namespace App\Asana\Service;

use App\Asana\Domain\Project;
use App\Asana\Domain\Task;
use App\Asana\Domain\User;
use App\Asana\Domain\Workspace;
use Asana\Client;
use function array_key_exists;

class AsanaService
{
    /** @var Client */
    private $client;

    /** @var string */
    private $defaultWorkspace;

    /** @var string */
    private $defaultProject;

    /** @var Workspace[] */
    private $workspaces;

    /** @var Project[] */
    private $projects;

    /** @var User[] */
    private $users = [];

    public function __construct(Client $client, string $workspace, string $project)
    {
        $this->client           = $client;
        $this->defaultWorkspace = $workspace;
        $this->defaultProject   = $project;
    }

    /**
     * Returns user for given id.
     *
     * Can be one of an email address, the globally unique identifier for the user, or the keyword `me`
     * to indicate the current user making the request.
     */
    public function getUser(string $id) : User
    {
        if (array_key_exists($id, $this->users)) {
            return $this->users[$id];
        }

        return User::fromState((array) $this->client->users->findById($id));
    }

    public function getWorkspace() : Workspace
    {
        if (! $this->workspaces) {
            $workspaceIterator = $this->client->workspaces->findAll();
            foreach ($workspaceIterator as $workspace) {
                $this->workspaces[$workspace->name] = Workspace::fromState((array) $workspace);
            }
        }

        return $this->workspaces[$this->defaultWorkspace];
    }

    public function getProject() : Project
    {
        $workspace = $this->getWorkspace();
        if (! $this->projects) {
            $projectIterator = $this->client->projects->findByWorkspace($workspace->id());
            foreach ($projectIterator as $project) {
                $this->projects[$project->name] = Project::fromState((array) $project);
            }
        }

        if (! array_key_exists($this->defaultProject, $this->projects)) {
            $project = $this->client->projects->createInWorkspace(
                $workspace->id(),
                ['name' => $this->defaultProject]
            );

            $this->projects[$project->name] = Project::fromState((array) $project);
        }

        return $this->projects[$this->defaultProject];
    }

    public function findTask(string $projectCode) : ?Task
    {
        $taskIterator = $this->client->tasks->search(
            $this->getWorkspace()->id(),
            [
                'text'         => $projectCode,
                'projects.any' => $this->getProject()->id(),
            ],
            [
                'fields'     => ['id', 'name', 'note', 'completed', 'external'],
                'item_limit' => 1,
            ]
        );

        $task = null;
        foreach ($taskIterator as $task) {
            $task = Task::fromState((array) $task);
        }

        return $task;
    }

    public function saveTask(Task $task) : Task
    {
        if ($task->id() === null) {
            return $this->createTask($task);
        }

        return $this->updateTask($task);
    }

    private function createTask(Task $task) : Task
    {
        $updatedTask = $this->client->tasks->createInWorkspace($this->getWorkspace()->id(), [
            'name'      => $task->name(),
            'projects'  => [$this->getProject()->id()],
            'assignee'  => [
                'id' => $this->getUser('me')->id(),
            ],
            'completed' => $task->completed(),
            'notes'     => $task->notes(),
            //'external'  => ['github_id' => 'https://github.com/xtreamwayz/xtreamlabs.com/issues/3'],
        ]);

        return Task::fromState((array) $updatedTask);
    }

    private function updateTask(Task $task) : Task
    {
        $updatedTask = $this->client->tasks->update($task->id(), [
            'name'      => $task->name(),
            'notes'     => $task->notes(),
            'completed' => $task->completed(),
        ]);

        return Task::fromState((array) $updatedTask);
    }

    public function deleteTask(Task $task) : void
    {
        $this->client->tasks->delete($task->id());
    }
}
