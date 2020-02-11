<?php

declare(strict_types=1);

namespace App\Slack\Middleware;

use App\Slack\Message\DeployMessage;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Laminas\Diactoros\Response\JsonResponse;
use function array_keys;
use function implode;
use function in_array;
use function preg_match;
use function sprintf;
use function trim;

/**
 * @see https://api.slack.com/events-api
 */
class DeployHandler implements RequestHandlerInterface
{
    /** @var MessageBusInterface */
    private $bus;

    /** @var array $config */
    private $config;

    public function __construct(MessageBusInterface $bus, array $config)
    {
        $this->bus    = $bus;
        $this->config = $config;
    }

    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        // Enforce json request
        $request = $request->withHeader('Accept', 'application/json');
        $payload = (array) $request->getParsedBody();
        if (! isset($payload['command']) || $payload['command'] !== '/deploy') {
            return new JsonResponse([
                'response_type' => 'ephemeral',
                'text'          => 'Expected /deploy command',
            ]);
        }

        $projects = array_keys($this->config);
        $text     = trim($payload['text']);
        if ($text === '' || $text === 'help' || $text === 'list') {
            return new JsonResponse([
                'response_type' => 'ephemeral',
                'text'          => sprintf(
                    "Usage: `/deploy [project] [branch]`\nAvailable projects for deployment: `%s`.",
                    implode('`, `', $projects)
                ),
            ]);
        }

        $pattern = '/^(?<project>[a-z\-\.\_]+) (?<branch>[a-z\/\-\.\_]+)$/i';
        if (! isset($payload['text']) || preg_match($pattern, $payload['text'], $matches) !== 1) {
            return new JsonResponse([
                'response_type' => 'ephemeral',
                'text'          => sprintf(
                    "Invalid command. Usage: `/deploy [project] [branch]`\nAvailable projects for deployment: `%s`.",
                    implode('`, `', $projects)
                ),
            ]);
        }

        $project = $matches['project'];
        $branch  = $matches['branch'];
        if (! in_array($project, $projects, true)) {
            return new JsonResponse([
                'response_type' => 'ephemeral',
                'text'          => sprintf(
                    "Invalid project. Usage: `/deploy [project] [branch]`\nAvailable projects for deployment: `%s`.",
                    implode('`, `', $projects)
                ),
            ]);
        }

        $message = new DeployMessage($project, $branch);
        $this->bus->dispatch($message);

        return new JsonResponse([
            'response_type' => 'ephemeral',
            'text'          => sprintf(
                'Deployment queued for `%s::%s`',
                $project,
                $branch
            ),
        ]);
    }
}
