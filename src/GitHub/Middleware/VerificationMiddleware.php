<?php

declare(strict_types=1);

namespace App\GitHub\Middleware;

use DomainException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use function explode;
use function hash_hmac;

class VerificationMiddleware implements MiddlewareInterface
{
    /** @var string */
    private $secret;

    public function __construct(string $secret)
    {
        $this->secret = $secret;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface
    {
        $payloadSignature = $request->getHeaderLine('X-Hub-Signature');
        if (! $payloadSignature) {
            throw new DomainException('No GitHub payload signature headers present', 400);
        }

        $payloadSignature = explode('=', $payloadSignature, 2);
        if (! isset($payloadSignature[0], $payloadSignature[1])) {
            throw new DomainException('Invalid payload signature', 400);
        }

        if ($payloadSignature[0] !== 'sha1') {
            // see https://developer.github.com/webhooks/securing/
            throw new DomainException('X-Hub-Signature contains invalid algorithm', 400);
        }

        $payload     = (string) $request->getBody();
        $payloadHash = hash_hmac($payloadSignature[0], $payload, $this->secret);
        if ($payloadHash !== $payloadSignature[1]) {
            throw new DomainException('X-Hub-Signature does not match payload signature', 400);
        }

        return $handler->handle($request);
    }
}
