<?php

declare(strict_types=1);

namespace App\Slack\Middleware;

use DomainException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class VerificationMiddleware implements MiddlewareInterface
{
    /** @var string */
    private $token;

    /** @var string */
    private $teamId;

    public function __construct(string $token, string $teamId)
    {
        $this->token  = $token;
        $this->teamId = $teamId;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface
    {
        $payload = (array) $request->getParsedBody();
        if (! isset($payload['token'])) {
            throw new DomainException('Missing token', 400);
        }

        if ($this->token !== $payload['token']) {
            throw new DomainException('Invalid token', 400);
        }

        if (! isset($payload['team_id'])) {
            throw new DomainException('Missing team id', 400);
        }

        if ($this->teamId !== $payload['team_id']) {
            throw new DomainException('Invalid team id', 400);
        }

        return $handler->handle($request);
    }
}
