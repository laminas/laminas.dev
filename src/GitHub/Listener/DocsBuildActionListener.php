<?php

declare(strict_types=1);

namespace App\GitHub\Listener;

use App\GitHub\Event\DocsBuildAction;
use GuzzleHttp\Client;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Log\LoggerInterface;

class DocsBuildActionListener
{
    /** @var Client */
    private $httpClient;

    /** @var LoggerInterface */
    private $logger;

    /** @var RequestFactoryInterface */
    private $requestFactory;

    /** @var string */
    private $token;

    public function __construct(
        string $token,
        Client $httpClient,
        RequestFactoryInterface $requestFactory,
        LoggerInterface $logger
    ) {
        $this->token          = $token;
        $this->httpClient     = $httpClient;
        $this->requestFactory = $requestFactory;
        $this->logger         = $logger;
    }

    public function __invoke(DocsBuildAction $docsAction): void
    {
        $request = $this->requestFactory->createRequest(
            'POST',
            sprintf('https://api.github.com/repos/%s/dispatches', $docsAction->repo())
        );
        $request = $request
            ->withHeader('Authorization', sprintf('token %s', $this->token))
            ->withHeader('Accept', 'application/json')
            ->withHeader('Content-Type', 'application/json');
        $request->getBody()->write(json_encode(
            ['event_type' => 'docs-build'],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        ));

        $response = $this->client->send($request);
        if ($response->getStatusCode() !== 204) {
            $this->logger->error(sprintf(
                'Error attempting to trigger documentation build for %s: %s',
                $docsAction->repo(),
                (string) $response->getBody()
            ));
        }
    }
}
