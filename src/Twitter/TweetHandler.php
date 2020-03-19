<?php

declare(strict_types=1);

namespace App\Twitter;

use DateTimeImmutable;
use DateTimeInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function preg_match;
use function sprintf;
use function trim;

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
            $this->createDateTimeFromString($params['timestamp'])
        ));

        return $this->responseFactory->createResponse(202);
    }

    private function createDateTimeFromString(string $timestamp): DateTimeInterface
    {
        $matches = [];
        if (! preg_match('/^(?P<date>.*?)\s+at\s+(?P<time>.*)$/', $timestamp, $matches)) {
            return new DateTimeImmutable('now');
        }

        $date = trim($matches['date']);
        $time = trim($matches['time']);

        return new DateTimeImmutable(sprintf('%s %s', $date, $time));
    }
}
