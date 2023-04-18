<?php

declare(strict_types=1);

namespace AppTest\GitHub\Listener;

use App\GitHub\Event\DocsBuildAction;
use App\GitHub\GitHubClient;
use App\GitHub\Listener\DocsBuildActionListener;
use App\Slack\Domain\SlashResponseMessage;
use App\Slack\Response\SlackResponse;
use App\Slack\SlackClientInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;

class DocsBuildActionListenerTest extends TestCase
{
    use ProphecyTrait;

    /** @var GitHubClient&ObjectProphecy */
    private $githubClient;
    /** @var LoggerInterface&ObjectProphecy */
    private $logger;
    /** @var SlackClientInterface&ObjectProphecy */
    private $slack;
    /** @var DocsBuildActionListener */
    private $listener;

    public function setUp(): void
    {
        $this->githubClient = $this->prophesize(GitHubClient::class);
        $this->logger       = $this->prophesize(LoggerInterface::class);
        $this->slack        = $this->prophesize(SlackClientInterface::class);

        $this->listener = new DocsBuildActionListener(
            $this->githubClient->reveal(),
            $this->logger->reveal(),
            $this->slack->reveal()
        );
    }

    public function httpErrorStatuses(): iterable
    {
        for ($i = 100; $i < 203; $i += 1) {
            yield $i => [$i];
        }

        for ($i = 205; $i < 600; $i += 1) {
            yield $i => [$i];
        }
    }

    /** @dataProvider httpErrorStatuses */
    public function testLogsErrorAndReportsViaSlackIfGitHubRequestFails(int $httpStatus): void
    {
        $repo        = 'laminas/some-repo';
        $responseUrl = 'https://hooks.slack.com/t/XXXX/YYYY/ZZZZZ';
        $docsAction  = new DocsBuildAction($repo, $responseUrl);

        $body = $this->prophesize(StreamInterface::class);
        $body
            ->write(Argument::that(function ($json) {
                TestCase::assertSame('{"event_type":"docs-build"}', $json);
                return $json;
            }))
            ->shouldBeCalled();

        $request = $this->prophesize(RequestInterface::class);
        $request->getBody()->will([$body, 'reveal'])->shouldBeCalled();

        $this->githubClient
            ->createRequest('POST', Argument::containingString('api.github.com/repos/laminas/some-repo/dispatches'))
            ->will([$request, 'reveal'])
            ->shouldBeCalled();

        $response = $this->prophesize(ResponseInterface::class);
        $response->getStatusCode()->willReturn($httpStatus)->shouldBeCalled();
        $response->getBody()->willReturn('error message from github')->shouldBeCalled();

        $this->githubClient->send($request->reveal())->will([$response, 'reveal'])->shouldBeCalled();

        $this->logger
            ->error(Argument::that(function ($message) {
                TestCase::assertStringContainsString('Error attempting to trigger documentation build', $message);
                TestCase::assertStringContainsString('laminas/some-repo', $message);
                TestCase::assertStringContainsString('error message from github', $message);
                return $message;
            }))
            ->shouldBeCalled();

        $slackResponse = $this->prophesize(SlackResponse::class)->reveal();
        $this->slack
            ->sendWebhookMessage($responseUrl, Argument::that(function ($message) {
                TestCase::assertInstanceOf(SlashResponseMessage::class, $message);
                $payload = $message->toArray();
                TestCase::assertArrayHasKey('text', $payload);
                TestCase::assertStringContainsString('Error queueing documentation build', $payload['text']);

                return $message;
            }))
            ->willReturn($slackResponse)
            ->shouldBeCalled();

        $this->assertNull($this->listener->__invoke($docsAction));
    }

    public function testDoesNotLogOrReportToSlackOnGitHubAPISuccess(): void
    {
        $repo        = 'laminas/some-repo';
        $responseUrl = 'https://hooks.slack.com/t/XXXX/YYYY/ZZZZZ';
        $docsAction  = new DocsBuildAction($repo, $responseUrl);

        $body = $this->prophesize(StreamInterface::class);
        $body
            ->write(Argument::that(function ($json) {
                TestCase::assertSame('{"event_type":"docs-build"}', $json);
                return $json;
            }))
            ->shouldBeCalled();

        $request = $this->prophesize(RequestInterface::class);
        $request->getBody()->will([$body, 'reveal'])->shouldBeCalled();

        $this->githubClient
            ->createRequest('POST', Argument::containingString('api.github.com/repos/laminas/some-repo/dispatches'))
            ->will([$request, 'reveal'])
            ->shouldBeCalled();

        $response = $this->prophesize(ResponseInterface::class);
        $response->getStatusCode()->willReturn(204)->shouldBeCalled();

        $this->githubClient->send($request->reveal())->will([$response, 'reveal'])->shouldBeCalled();

        $this->logger
            ->error(Argument::any())
            ->shouldNotBeCalled();

        $this->slack
            ->sendWebhookMessage(Argument::any(), Argument::any())
            ->shouldNotBeCalled();

        $this->assertNull($this->listener->__invoke($docsAction));
    }
}
