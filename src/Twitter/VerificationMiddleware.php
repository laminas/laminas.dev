<?php

declare(strict_types=1);

namespace App\Twitter;

use Assert\Assert;
use Assert\AssertionFailedException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class VerificationMiddleware implements MiddlewareInterface
{
    /** @var ResponseFactoryInterface */
    private $responseFactory;

    /** @var string */
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

        $data = $request->getParsedBody();

        try {
            $this->validateRequestBody($data);
        } catch (AssertionFailedException $e) {
            return $this->responseFactory->createResponse(400);
        }

        return $handler->handle($request);
    }

    /**
     * @param mixed $data
     * @throws AssertionFailedException
     */
    private function validateRequestBody($data): void
    {
        Assert::that($data)->isArray();

        Assert::that($data)->keyIsset('text');
        Assert::that($data['text'])->string()->notEmpty();

        Assert::that($data)->keyIsset('url');
        Assert::that($data['url'])->url()->regex('#^https://twitter.com#');

        Assert::that($data)->keyIsset('timestamp');
    }
}
