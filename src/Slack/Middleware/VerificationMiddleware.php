<?php

declare(strict_types=1);

namespace App\Slack\Middleware;

use Mezzio\ProblemDetails\ProblemDetailsResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class VerificationMiddleware implements MiddlewareInterface
{
    /** @var ProblemDetailsResponseFactory */
    private $responseFactory;

    /** @var string */
    private $token;

    /** @var string */
    private $teamId;

    public function __construct(string $token, string $teamId, ProblemDetailsResponseFactory $responseFactory)
    {
        $this->token           = $token;
        $this->teamId          = $teamId;
        $this->responseFactory = $responseFactory;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface
    {
        $payload = (array) $request->getParsedBody();
        if (! isset($payload['token'])) {
            return $this->createErrorResponse(400, 'Missing token', $request);
        }

        if ($this->token !== $payload['token']) {
            return $this->createErrorResponse(400, 'Invalid token', $request);
        }

        if (! isset($payload['team_id'])) {
            return $this->createErrorResponse(400, 'Missing team id', $request);
        }

        if ($this->teamId !== $payload['team_id']) {
            return $this->createErrorResponse(400, 'Invalid team id', $request);
        }

        return $handler->handle($request);
    }

    private function createErrorResponse(int $status, string $message, ServerRequestInterface $request): ResponseInterface
    {
        return $this->responseFactory->createResponse(
            $request,
            $status,
            $message
        );
    }
}
