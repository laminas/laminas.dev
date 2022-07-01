<?php

declare(strict_types=1);

namespace AppTest\Slack;

use App\HttpClientInterface;
use App\Slack\Domain\SlashResponseMessage;
use App\Slack\Domain\WebAPIMessage;
use App\Slack\Response\SlackResponse;
use App\Slack\SlackClient;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;

use function json_encode;

use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

class SlackClientTest extends TestCase
{
    use ProphecyTrait;

    /** @var HttpClientInterface|ObjectProphecy */
    private $httpClient;

    /** @var LoggerInterface|ObjectProphecy */
    private $logger;

    /** @var SlackClient */
    private $slack;

    public function setUp(): void
    {
        $this->httpClient = $this->prophesize(HttpClientInterface::class);
        $this->logger     = $this->prophesize(LoggerInterface::class);

        $this->slack = new SlackClient(
            $this->httpClient->reveal(),
            'slack-api-token',
            $this->logger->reveal()
        );
    }

    public function testSendReturnsReceivedResponseOnSuccess(): void
    {
        $request = $this->prophesize(RequestInterface::class);
        $request
            ->withHeader('Authorization', 'Bearer slack-api-token')
            ->will([$request, 'reveal'])
            ->shouldBeCalled();

        $response = $this->prophesize(ResponseInterface::class);
        $response
            ->getBody()
            ->willReturn(json_encode([
                'ok' => true,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
            ->shouldBeCalled();

        $this->httpClient
            ->send($request->reveal())
            ->will([$response, 'reveal'])
            ->shouldBeCalled();

        $this->httpClient->createRequest(Argument::any())->shouldNotBeCalled();
        $this->logger->error(Argument::any())->shouldNotBeCalled();

        $slackResponse = $this->slack->send($request->reveal());
        $this->assertInstanceOf(SlackResponse::class, $slackResponse);
        $this->assertSame($response->reveal(), $slackResponse->getResponse());
    }

    public function testSendLogsResponseOnFailureBeforeReturning(): void
    {
        $request = $this->prophesize(RequestInterface::class);
        $request
            ->withHeader('Authorization', 'Bearer slack-api-token')
            ->will([$request, 'reveal'])
            ->shouldBeCalled();

        $response = $this->prophesize(ResponseInterface::class);
        $response->getStatusCode()->willReturn(400)->shouldBeCalled();
        $response
            ->getBody()
            ->willReturn(json_encode([
                'ok'    => false,
                'error' => 'the error message',
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
            ->shouldBeCalled();

        $this->httpClient
            ->send($request->reveal())
            ->will([$response, 'reveal'])
            ->shouldBeCalled();

        $this->httpClient->createRequest(Argument::any())->shouldNotBeCalled();
        $this->logger
            ->error(
                Argument::containingString('SlackClient: error sending message'),
                Argument::that(function ($context) {
                    TestCase::assertArrayHasKey('code', $context);
                    TestCase::assertArrayHasKey('error', $context);
                    TestCase::assertSame(400, $context['code']);
                    TestCase::assertSame('the error message', $context['error']);

                    return $context;
                })
            )
            ->shouldBeCalled();

        $slackResponse = $this->slack->send($request->reveal());
        $this->assertInstanceOf(SlackResponse::class, $slackResponse);
        $this->assertSame($response->reveal(), $slackResponse->getResponse());
    }

    public function testSendWebAPIMessageLogsErrorWithoutSendingRequestWhenMessageIsInvalid(): void
    {
        $message = new WebAPIMessage();
        $this->logger
            ->error(
                Argument::containingString('SlackClient: invalid message provided'),
                Argument::that(function ($context) use ($message) {
                    TestCase::assertArrayHasKey('message', $context);
                    TestCase::assertArrayHasKey('error', $context);
                    TestCase::assertSame($message->toArray(), $context['message']);

                    return $context;
                })
            )
            ->shouldBeCalled();

        $this->httpClient->createRequest(Argument::any())->shouldNotBeCalled();
        $this->httpClient->send(Argument::any())->shouldNotBeCalled();

        $this->assertNull($this->slack->sendWebAPIMessage($message));
    }

    public function testSendWebhookMessageMarshalsRequestFromMessageAndSendsIt(): void
    {
        $responseUrl = 'webhook-response-url';
        $message     = new SlashResponseMessage();
        $message->setText('This is the message to send');

        $body = $this->prophesize(StreamInterface::class);
        $body
            ->write(json_encode(
                $message->toArray(),
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            ))
            ->shouldBeCalled();

        $request = $this->prophesize(RequestInterface::class);
        $request
            ->withHeader(
                Argument::that(function ($header) {
                    TestCase::assertMatchesRegularExpression('/^(Content-Type|Accept|Authorization)$/', $header);
                    return $header;
                }),
                Argument::that(function ($value) {
                    TestCase::assertMatchesRegularExpression('#^(application/json|Bearer slack-api-token)#', $value);
                    return $value;
                })
            )
            ->will([$request, 'reveal'])
            ->shouldBeCalledTimes(3);
        $request->getBody()->will([$body, 'reveal'])->shouldBeCalled();

        $this->httpClient
            ->createRequest('POST', $responseUrl)
            ->will([$request, 'reveal'])
            ->shouldBeCalled();

        $response = $this->prophesize(ResponseInterface::class);
        $response
            ->getBody()
            ->willReturn(json_encode([
                'ok' => true,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
            ->shouldBeCalled();

        $this->httpClient
            ->send($request->reveal())
            ->will([$response, 'reveal'])
            ->shouldBeCalled();

        $this->logger->error(Argument::any())->shouldNotBeCalled();

        $slackResponse = $this->slack->sendWebhookMessage($responseUrl, $message);
        $this->assertInstanceOf(SlackResponse::class, $slackResponse);
        $this->assertSame($response->reveal(), $slackResponse->getResponse());
    }

    public function testSendWebhookMessageLogsErrorWithoutSendingRequestWhenMessageIsInvalid(): void
    {
        $message = new SlashResponseMessage();
        $this->logger
            ->error(
                Argument::containingString('SlackClient: invalid message provided'),
                Argument::that(function ($context) use ($message) {
                    TestCase::assertArrayHasKey('message', $context);
                    TestCase::assertArrayHasKey('error', $context);
                    TestCase::assertSame($message->toArray(), $context['message']);

                    return $context;
                })
            )
            ->shouldBeCalled();

        $this->httpClient->createRequest(Argument::any())->shouldNotBeCalled();
        $this->httpClient->send(Argument::any())->shouldNotBeCalled();

        $this->assertNull($this->slack->sendWebhookMessage('some-url', $message));
    }

    public function testSendWebAPIMessageMarshalsRequestFromMessageAndSendsIt(): void
    {
        $message = new WebAPIMessage();
        $message->setChannel('#github');
        $message->setText('This is the message to send');

        $body = $this->prophesize(StreamInterface::class);
        $body
            ->write(json_encode(
                $message->toArray(),
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            ))
            ->shouldBeCalled();

        $request = $this->prophesize(RequestInterface::class);
        $request
            ->withHeader(
                Argument::that(function ($header) {
                    TestCase::assertMatchesRegularExpression('/^(Content-Type|Accept|Authorization)$/', $header);
                    return $header;
                }),
                Argument::that(function ($value) {
                    TestCase::assertMatchesRegularExpression('#^(application/json|Bearer slack-api-token)#', $value);
                    return $value;
                })
            )
            ->will([$request, 'reveal'])
            ->shouldBeCalledTimes(3);
        $request->getBody()->will([$body, 'reveal'])->shouldBeCalled();

        $this->httpClient
            ->createRequest('POST', SlackClient::ENDPOINT_CHAT)
            ->will([$request, 'reveal'])
            ->shouldBeCalled();

        $response = $this->prophesize(ResponseInterface::class);
        $response
            ->getBody()
            ->willReturn(json_encode([
                'ok' => true,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
            ->shouldBeCalled();

        $this->httpClient
            ->send($request->reveal())
            ->will([$response, 'reveal'])
            ->shouldBeCalled();

        $this->logger->error(Argument::any())->shouldNotBeCalled();

        $slackResponse = $this->slack->sendWebAPIMessage($message);
        $this->assertInstanceOf(SlackResponse::class, $slackResponse);
        $this->assertSame($response->reveal(), $slackResponse->getResponse());
    }
}
