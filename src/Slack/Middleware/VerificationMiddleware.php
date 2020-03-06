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
    private $secret;

    public function __construct(string $secret, ProblemDetailsResponseFactory $responseFactory)
    {
        $this->secret          = $secret;
        $this->responseFactory = $responseFactory;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $signature = $request->getHeaderLine('X-Slack-Signature');
        if (empty($signature)) {
            return $this->createErrorResponse(400, 'Missing signature', $request);
        }

        $ts = $request->getHeaderLine('X-Slack-Request-Timestamp');
        if (empty($ts)) {
            return $this->createErrorResponse(400, 'Missing timestamp', $request);
        }
        $ts = (int) $ts;

        if (time() - $ts > 300) {
            return $this->createErrorResponse(400, 'Invalid timestamp', $request);
        }

        $token = sprintf('v0:%d:%s', $ts, (string) $request->getBody());
        if (hash_hmac('sha256', $token, $this->secret) !== $signature) {
            return $this->createErrorResponse(400, 'Invalid signature', $request);
        }

        return $handler->handle($request);
    }

    private function createErrorResponse(
        int $status,
        string $message,
        ServerRequestInterface $request
    ): ResponseInterface {
        return $this->responseFactory->createResponse(
            $request,
            $status,
            $message
        );
    }
}
