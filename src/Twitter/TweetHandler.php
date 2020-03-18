<?php

declare(strict_types=1);

namespace App\Twitter;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Log\LoggerInterface;

class TweetHandler implements RequestHandlerInterface
{
    /** @var LoggerInterface */
    private $logger;

    /** @var ResponseFactoryInterface */
    private $responseFactory;

    public function __construct(ResponseFactoryInterface $responseFactory, LoggerInterface $logger)
    {
        $this->responseFactory = $responseFactory;
        $this->logger          = $logger;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->logger->info(sprintf('[twitter] %s', (string) $request->getBody()));
        return $this->responseFactory->createResponse(202);
    }
}
