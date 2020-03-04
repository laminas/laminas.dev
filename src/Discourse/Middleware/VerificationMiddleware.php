<?php

declare(strict_types=1);

namespace App\Discourse\Middleware;

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
        $signature = $request->getHeaderLine('X-Discourse-Event-Signature');
        if (empty($signature)) {
            return $this->createErrorResponse(400, 'No Discourse payload signature headers present', $request);
        }

        if (strpos($signature, 'sha256=') === 0) {
            $signature = substr($signature, 7);
        }

        $body = (string) $request->getBody();
        if (hash_hmac('sha256', $body, $this->secret) !== $signature) {
            return $this->createErrorResponse(203, 'Invalid or missing signature', $request);
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
