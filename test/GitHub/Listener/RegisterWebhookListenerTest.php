<?php

declare(strict_types=1);

namespace AppTest\GitHub\Listener;

use App\GitHub\Event\RegisterWebhook;
use App\GitHub\GitHubClient;
use App\GitHub\Listener\RegisterWebhookListener;
use App\Slack\Domain\SlashResponseMessage;
use App\Slack\Response\SlackResponseInterface;
use App\Slack\SlackClientInterface;
use App\UrlHelper;
use AppTest\Psr7Helper;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;

use function json_decode;

class RegisterWebhookListenerTest extends TestCase
{
    use ProphecyTrait;

    /** @var GitHubClient|ObjectProphecy */
    private $github;

    /** @var LoggerInterface|ObjectProphecy */
    private $logger;

    /** @var string */
    private $secret;

    /** @var SlackClientInterface|ObjectProphecy */
    private $slack;

    /** @var UrlHelper|ObjectProphecy */
    private $url;
    /** @var RegisterWebhookListener */
    private $listener;

    public function setUp(): void
    {
        $this->secret = 'XXXXXXXX';
        $this->github = $this->prophesize(GitHubClient::class);
        $this->url    = $this->prophesize(UrlHelper::class);
        $this->logger = $this->prophesize(LoggerInterface::class);
        $this->slack  = $this->prophesize(SlackClientInterface::class);

        $this->listener = new RegisterWebhookListener(
            $this->secret,
            $this->github->reveal(),
            $this->url->reveal(),
            $this->logger->reveal(),
            $this->slack->reveal()
        );
    }

    public function testNotifiesSlackOnSuccessfulWebhookRegistration(): void
    {
        $repo        = 'laminas/laminas-component';
        $responseUrl = 'slack-response-webhook';
        $secret      = $this->secret;
        $event       = new RegisterWebhook($repo, $responseUrl);

        $this->url->generate('api.github')->willReturn('the-github-api-url')->shouldBeCalled();

        $body = $this->prophesize(StreamInterface::class);
        $body
            ->write(Argument::that(function ($json) use ($secret) {
                $data = json_decode($json, true);
                TestCase::assertSame($secret, $data['config']['secret']);
                TestCase::assertSame('the-github-api-url', $data['config']['url']);
                TestCase::assertSame([
                    'issues',
                    'issue_comment',
                    'pull_request',
                    'release',
                    'status',
                ], $data['events']);

                return $json;
            }))
            ->shouldBeCalled();

        $request = $this->prophesize(RequestInterface::class);
        $request->getBody()->will([$body, 'reveal'])->shouldBeCalled();

        $response = $this->prophesize(ResponseInterface::class);
        $response->getStatusCode()->willReturn(201)->shouldBeCalled();

        $this->github
            ->createRequest(
                'POST',
                Argument::containingString('api.github.com/repos/laminas/laminas-component/hooks')
            )
            ->will([$request, 'reveal'])
            ->shouldBeCalled();

        $this->github->send($request->reveal())->will([$response, 'reveal'])->shouldBeCalled();

        $slackResponse = $this->prophesize(SlackResponseInterface::class)->reveal();

        $this->slack
            ->sendWebhookMessage(
                $responseUrl,
                Argument::that(function ($message) use ($repo) {
                    TestCase::assertInstanceOf(SlashResponseMessage::class, $message);
                    TestCase::assertSame('laminas-bot webhook for ' . $repo . ' registered', $message->getText());

                    return $message;
                })
            )
            ->willReturn($slackResponse)
            ->shouldBeCalled();

        $this->assertNull($this->listener->__invoke($event));
    }

    public function testLogsErrorAndNotifiesSlackWhenWebhookRegistrationFails(): void
    {
        $repo        = 'laminas/laminas-component';
        $responseUrl = 'slack-response-webhook';
        $secret      = $this->secret;
        $event       = new RegisterWebhook($repo, $responseUrl);

        $this->url->generate('api.github')->willReturn('the-github-api-url')->shouldBeCalled();

        $body = $this->prophesize(StreamInterface::class);
        $body
            ->write(Argument::that(function ($json) use ($secret) {
                $data = json_decode($json, true);
                TestCase::assertSame($secret, $data['config']['secret']);
                TestCase::assertSame('the-github-api-url', $data['config']['url']);
                TestCase::assertSame([
                    'issues',
                    'issue_comment',
                    'pull_request',
                    'release',
                    'status',
                ], $data['events']);

                return $json;
            }))
            ->shouldBeCalled();

        $request = $this->prophesize(RequestInterface::class);
        $request->getBody()->will([$body, 'reveal'])->shouldBeCalled();

        $response = $this->prophesize(ResponseInterface::class);
        $response->getStatusCode()->willReturn(400)->shouldBeCalled();
        $response->getBody()->willReturn(Psr7Helper::stream(''));

        $this->github
            ->createRequest(
                'POST',
                Argument::containingString('api.github.com/repos/laminas/laminas-component/hooks')
            )
            ->will([$request, 'reveal'])
            ->shouldBeCalled();

        $this->github->send($request->reveal())->will([$response, 'reveal'])->shouldBeCalled();

        $this->logger
            ->error(Argument::containingString('Error registering laminas-bot webhook for ' . $repo))
            ->shouldBeCalled();

        $slackResponse = $this->prophesize(SlackResponseInterface::class)->reveal();
        $this->slack
            ->sendWebhookMessage(
                $responseUrl,
                Argument::that(function ($message) use ($repo) {
                    TestCase::assertInstanceOf(SlashResponseMessage::class, $message);
                    TestCase::assertStringContainsString(
                        'Error registering laminas-bot webhook for ' . $repo,
                        $message->getText()
                    );

                    return $message;
                })
            )
            ->willReturn($slackResponse)
            ->shouldBeCalled();

        $this->assertNull($this->listener->__invoke($event));
    }
}
