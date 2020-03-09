<?php

declare(strict_types=1);

namespace App\GitHub;

use App\HttpClientInterface;
use Laminas\Diactoros\Request\Serializer as RequestSerializer;
use Laminas\Diactoros\Response\Serializer as ResponseSerializer;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

use function sprintf;

class GitHubClient implements HttpClientInterface
{
    /** @var HttpClientInterface */
    private $httpClient;

    /** @var null|LoggerInterface */
    private $logger;

    /** @var string */
    private $token;

    public function __construct(
        string $token,
        HttpClientInterface $httpClient,
        ?LoggerInterface $logger
    ) {
        $this->token      = $token;
        $this->httpClient = $httpClient;
        $this->logger     = $logger;
    }

    public function send(RequestInterface $request): ResponseInterface
    {
        $this->logRequest($request);
        $response = $this->httpClient->send($request);
        $this->logResponse($response);
        return $response;
    }

    /** @param string|UriInterface $url */
    public function createRequest(string $method, $url): RequestInterface
    {
        return $this->httpClient->createRequest($method, $url)
            ->withHeader('Authorization', sprintf('token %s', $this->token))
            ->withHeader('Accept', 'application/json')
            ->withHeader('Content-Type', 'application/json');
    }

    private function log(string $message): void
    {
        if (! $this->logger) {
            return;
        }

        $this->logger->info($message);
    }

    private function logRequest(RequestInterface $request): void
    {
        if (! $this->logger) {
            return;
        }
        $this->log(sprintf("Sending request to GitHub:\n%s", RequestSerializer::toString($request)));
    }

    private function logResponse(ResponseInterface $response): void
    {
        if (! $this->logger) {
            return;
        }
        $this->log(sprintf("Sending response to GitHub:\n%s", ResponseSerializer::toString($response)));
    }
}
