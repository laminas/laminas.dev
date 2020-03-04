<?php

declare(strict_types=1);

namespace App\GitHub\Middleware;

use Mezzio\ProblemDetails\ProblemDetailsResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function explode;
use function hash_hmac;

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

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface
    {
        $payloadSignature = $request->getHeaderLine('X-Hub-Signature');
        if (! $payloadSignature) {
            return $this->createErrorResponse(400, 'No GitHub payload signature headers present', $request);
        }

        $payloadSignature = explode('=', $payloadSignature, 2);
        if (! isset($payloadSignature[0], $payloadSignature[1])) {
            return $this->createErrorResponse(400, 'Invalid payload signature', $request);
        }

        if ($payloadSignature[0] !== 'sha1') {
            // see https://developer.github.com/webhooks/securing/
            return $this->createErrorResponse(400, 'X-Hub-Signature contains invalid algorithm', $request);
        }

        $payload     = (string) $request->getBody();
        $payloadHash = hash_hmac($payloadSignature[0], $payload, $this->secret);
        if ($payloadHash !== $payloadSignature[1]) {
            return $this->createErrorResponse(400, 'X-Hub-Signature does not match payload signature', $request);
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
