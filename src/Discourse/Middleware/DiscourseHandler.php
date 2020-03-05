<?php

declare(strict_types=1);

namespace App\Discourse\Middleware;

use App\Discourse\Event\DiscoursePost;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class DiscourseHandler implements RequestHandlerInterface
{
    /** @var string */
    private $discourseUrl;

    /** @var EventDispatcherInterface */
    private $dispatcher;

    /** @var ResponseFactoryInterface */
    private $responseFactory;

    public function __construct(
        string $discourseUrl,
        EventDispatcherInterface $dispatcher,
        ResponseFactoryInterface $responseFactory
    ) {
        $this->discourseUrl    = $discourseUrl;
        $this->dispatcher      = $dispatcher;
        $this->responseFactory = $responseFactory;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->dispatcher->dispatch(new DiscoursePost(
            $request->getAttribute('channel'),
            $request->getParsedBody(),
            $this->discourseUrl
        ));

        return $this->responseFactory->createResponse(202);
    }
}
