<?php

declare(strict_types=1);

namespace AppTest\GitHub\Listener;

use App\GitHub\Event\GitHubRelease;
use App\GitHub\Listener\GitHubReleaseWebsiteUpdateListener;
use GuzzleHttp\Client as HttpClient;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;

use function date;
use function json_decode;

class GitHubReleaseWebsiteUpdateListenerTest extends TestCase
{
    /** @var HttpClient|ObjectProphecy */
    private $httpClient;

    /** @var GitHubReleaseWebsiteUpdateListener */
    private $listener;

    /** @var LoggerInterface|ObjectProphecy */
    private $logger;

    /** @var string */
    private $releaseApiUrl;

    /** @var RequestFactoryInterface|ObjectProphecy */
    private $requestFactory;

    /** @var string */
    private $token;

    public function setUp(): void
    {
        $this->httpClient     = $this->prophesize(HttpClient::class);
        $this->requestFactory = $this->prophesize(RequestFactoryInterface::class);
        $this->logger         = $this->prophesize(LoggerInterface::class);
        $this->token          = 'the-token';
        $this->releaseApiUrl  = 'injected-release-api-url';

        $this->listener = new GitHubReleaseWebsiteUpdateListener(
            $this->httpClient->reveal(),
            $this->requestFactory->reveal(),
            $this->logger->reveal(),
            $this->token,
            $this->releaseApiUrl
        );
    }

    public function testDoesNothingIfReleaseIsNotPublished(): void
    {
        $release = new GitHubRelease([
            'release' => [
                'draft' => true,
            ],
        ]);

        $this->requestFactory->createRequest(Argument::any(), Argument::any())->shouldNotBeCalled();
        $this->httpClient->send(Argument::any())->shouldNotBeCalled();

        $this->assertNull($this->listener->__invoke($release));
    }

    public function testLogsErrorUpdatingWebsite(): void
    {
        $payload = [
            'release'    => [
                'draft'        => false,
                'tag_name'     => '2.3.4p8',
                'html_url'     => 'release-url',
                'body'         => 'this is the changelog',
                'published_at' => date('r'),
                'author'       => [
                    'login'    => 'authorofrelease',
                    'html_url' => 'author-url',
                ],
            ],
            'repository' => [
                'full_name' => 'laminas/some-component',
            ],
        ];
        $release = new GitHubRelease($payload);

        $body = $this->prophesize(StreamInterface::class);
        $body
            ->write(Argument::that(function ($json) use ($payload) {
                $data = json_decode($json, true);
                TestCase::assertSame([
                    'package'          => $payload['repository']['full_name'],
                    'version'          => $payload['release']['tag_name'],
                    'url'              => $payload['release']['html_url'],
                    'changelog'        => $payload['release']['body'],
                    'publication_date' => $payload['release']['published_at'],
                    'author_name'      => $payload['release']['author']['login'],
                    'author_url'       => $payload['release']['author']['html_url'],
                ], $data);

                return $json;
            }))
            ->shouldBeCalled();

        $request = $this->prophesize(RequestInterface::class);
        $request
            ->withHeader(
                Argument::that(function ($header) {
                    TestCase::assertRegExp('/^(Accept|Content-Type|Authorization)$/', $header);
                    return $header;
                }),
                Argument::that(function ($value) {
                    TestCase::assertRegExp('#^(application/json|token the-token)#', $value);
                    return $value;
                })
            )
            ->will([$request, 'reveal'])
            ->shouldBeCalledTimes(3);
        $request->getBody()->will([$body, 'reveal'])->shouldBeCalled();

        $this->requestFactory
            ->createRequest('POST', $this->releaseApiUrl)
            ->will([$request, 'reveal'])
            ->shouldBeCalled();

        $response = $this->prophesize(ResponseInterface::class);
        $response->getStatusCode()->willReturn(400);
        $response->getBody()->willReturn('');

        $this->httpClient->send($request->reveal())->will([$response, 'reveal'])->shouldBeCalled();

        $this->logger
            ->error(Argument::containingString('Error notifying ' . $this->releaseApiUrl))
            ->shouldBeCalled();

        $this->assertNull($this->listener->__invoke($release));
    }

    public function testDoesNotLogWhenWebsiteUpdatedSuccessfully(): void
    {
        $payload = [
            'release'    => [
                'draft'        => false,
                'tag_name'     => '2.3.4p8',
                'html_url'     => 'release-url',
                'body'         => 'this is the changelog',
                'published_at' => date('r'),
                'author'       => [
                    'login'    => 'authorofrelease',
                    'html_url' => 'author-url',
                ],
            ],
            'repository' => [
                'full_name' => 'laminas/some-component',
            ],
        ];
        $release = new GitHubRelease($payload);

        $body = $this->prophesize(StreamInterface::class);
        $body
            ->write(Argument::that(function ($json) use ($payload) {
                $data = json_decode($json, true);
                TestCase::assertSame([
                    'package'          => $payload['repository']['full_name'],
                    'version'          => $payload['release']['tag_name'],
                    'url'              => $payload['release']['html_url'],
                    'changelog'        => $payload['release']['body'],
                    'publication_date' => $payload['release']['published_at'],
                    'author_name'      => $payload['release']['author']['login'],
                    'author_url'       => $payload['release']['author']['html_url'],
                ], $data);

                return $json;
            }))
            ->shouldBeCalled();

        $request = $this->prophesize(RequestInterface::class);
        $request
            ->withHeader(
                Argument::that(function ($header) {
                    TestCase::assertRegExp('/^(Accept|Content-Type|Authorization)$/', $header);
                    return $header;
                }),
                Argument::that(function ($value) {
                    TestCase::assertRegExp('#^(application/json|token the-token)#', $value);
                    return $value;
                })
            )
            ->will([$request, 'reveal'])
            ->shouldBeCalledTimes(3);
        $request->getBody()->will([$body, 'reveal'])->shouldBeCalled();

        $this->requestFactory
            ->createRequest('POST', $this->releaseApiUrl)
            ->will([$request, 'reveal'])
            ->shouldBeCalled();

        $response = $this->prophesize(ResponseInterface::class);
        $response->getStatusCode()->willReturn(200);
        $response->getBody()->shouldNotBeCalled();

        $this->httpClient->send($request->reveal())->will([$response, 'reveal'])->shouldBeCalled();

        $this->logger->error(Argument::any())->shouldNotBeCalled();

        $this->assertNull($this->listener->__invoke($release));
    }
}
