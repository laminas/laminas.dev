<?php

declare(strict_types=1);

namespace App;

use GuzzleHttp\Client as Guzzle;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

class HttpClient implements HttpClientInterface
{
    /** @var Guzzle */
    private $guzzle;

    /** @var RequestFactoryInterface */
    private $requestFactory;

    public function __construct(Guzzle $guzzle, RequestFactoryInterface $requestFactory)
    {
        $this->guzzle         = $guzzle;
        $this->requestFactory = $requestFactory;
    }

    public function send(RequestInterface $request): ResponseInterface
    {
        return $this->guzzle->send($request);
    }

    /** @param string|UriInterface $uri */
    public function createRequest(string $method, $uri): RequestInterface
    {
        return $this->requestFactory->createRequest($method, $uri);
    }
}
