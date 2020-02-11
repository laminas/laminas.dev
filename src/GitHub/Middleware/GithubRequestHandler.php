<?php

declare(strict_types=1);

namespace App\GitHub\Middleware;

use App\GitHub\Message;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\JsonResponse;

/**
 * @see https://developer.github.com/webhooks/
 */
class GithubRequestHandler implements RequestHandlerInterface
{
    /** @var MessageBusInterface */
    private $bus;

    public function __construct(MessageBusInterface $bus)
    {
        $this->bus = $bus;
    }

    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        $eventName = $request->getHeaderLine('X-GitHub-Event');
        $payload   = (array) $request->getParsedBody();

        switch ($eventName) {
            case 'pull_request':
                $message = new Message\GitHubPullRequest($payload);
                break;

            case 'push':
                $message = new Message\GitHubPush($payload);
                break;

            case 'status':
                $message = new Message\GitHubStatus($payload);
                break;

            case 'issues':
                $message = new Message\GitHubIssue($payload);
                break;

            case 'deployment':          // TODO: GitHub deployment event
            case 'deployment_status':   // TODO: GitHub deployment_status event
            default:
                $message = null;
                break;
        }

        if ($eventName === 'ping') {
            return new JsonResponse(['message' => 'Hello from XtreamLabs :D'], 204);
        }

        if ($message === null || $message->ignore()) {
            return new JsonResponse(['message' => 'Received but not processed.'], 204);
        }

        try {
            $message->validate();
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400, ['X-Status-Reason' => 'Validation failed']);
        }

        $this->bus->dispatch($message);

        return new EmptyResponse(204);
    }
}
