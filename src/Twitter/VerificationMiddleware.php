<?php

declare(strict_types=1);

namespace App\Twitter;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class VerificationMiddleware implements MiddlewareInterface
{
    /** @var ResponseFactoryInterface */
    private $responseFactory;

    /** @var string $verificationToken */
    private $verificationToken;

    public function __construct(string $verificationToken, ResponseFactoryInterface $responseFactory)
    {
        $this->verificationToken = $verificationToken;
        $this->responseFactory   = $responseFactory;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->verificationToken !== $request->getAttribute('token')) {
            return $this->responseFactory->createResponse(401);
        }
        return $handler->handle($request);
    }
}
