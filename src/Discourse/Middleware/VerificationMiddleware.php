<?php

declare(strict_types=1);

namespace App\Discourse\Middleware;

use Laminas\Stdlib\ResponseInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class VerificationMiddleware implements MiddlewareInterface
{
    /** @var ResponseFactoryInterface */
    private $responseFactory;

    /** @var string */
    private $secret;

    public function __construct(string $secret, ResponseFactoryInterface $responseFactory)
    {
        $this->secret          = $secret;
        $this->responseFactory = $responseFactory;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $signature = $request->getHeaderLine('X-Discourse-Event-Signature');
        if (empty($signature)) {
            return $this->responseFactory->createResponse(400);
        }

        if (preg_match('/^sha256\=/', $signature)) {
            $signature = substr($signature, 7);
        }

        $body = (string) $request->getBody();
        if (hash_hmac('sha256', $body, $this->secret) !== $signature) {
            return $this->responseFactory->create(203, 'Invalid or missing signature');
        }

        return $handler->handle($request);
    }
}
