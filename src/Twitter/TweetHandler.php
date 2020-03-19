<?php

declare(strict_types=1);

namespace App\Twitter;

use DateTimeImmutable;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class TweetHandler implements RequestHandlerInterface
{
    /** @var EventDispatcherInterface */
    private $dispatcher;

    /** @var ResponseFactoryInterface */
    private $responseFactory;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        EventDispatcherInterface $dispatcher
    ) {
        $this->responseFactory = $responseFactory;
        $this->dispatcher      = $dispatcher;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $params = $request->getParsedBody();

        $this->dispatcher->dispatch(new Tweet(
            $params['text'],
            $params['url'],
            new DateTimeImmutable($params['timestamp'])
        ));

        return $this->responseFactory->createResponse(202);
    }
}
