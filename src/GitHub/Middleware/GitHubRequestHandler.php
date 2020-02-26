<?php

declare(strict_types=1);

namespace App\GitHub\Middleware;

use App\GitHub\Event;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * @see https://developer.github.com/webhooks/
 */
class GithubRequestHandler implements RequestHandlerInterface
{
    /** @var EventDispatcherInterface */
    private $dispatcher;

    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $eventName = $request->getHeaderLine('X-GitHub-Event');
        $payload   = (array) $request->getParsedBody();

        switch ($eventName) {
            case 'issues':
                $message = new Event\GitHubIssue($payload);
                break;

            case 'issue_comment':
                $message = new Event\GitHubIssueComment($payload);
                break;

            case 'pull_request':
                $message = new Event\GitHubPullRequest($payload);
                break;

            case 'release':
                $message = new Event\GitHubRelease($payload);
                break;

            case 'status':
                $message = new Event\GitHubStatus($payload);
                break;

            default:
                $message = null;
                break;
        }

        if ($eventName === 'ping') {
            return new JsonResponse(['message' => 'Hello from Laminas Bot :D'], 204);
        }

        if ($message === null || $message->ignore()) {
            return new JsonResponse(['message' => 'Received but not processed.'], 204);
        }

        try {
            $message->validate();
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400, ['X-Status-Reason' => 'Validation failed']);
        }

        $this->dispatcher->dispatch($message);

        return new EmptyResponse(204);
    }
}
