<?php

declare(strict_types=1);

namespace AppTest\Slack\Listener;

use App\HttpClientInterface;
use App\Slack\Domain\SlashResponseMessage;
use App\Slack\Event\Tweet;
use App\Slack\Listener\TweetListener;
use App\Slack\SlackClientInterface;
use Laminas\Http\Client as TwitterHttpClient;
use Laminas\Twitter\Image;
use Laminas\Twitter\Response as TwitterResponse;
use Laminas\Twitter\Twitter;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

class TweetListenerTest extends TestCase
{
    use ProphecyTrait;

    /** @var HttpClientInterface|ObjectProphecy */
    private $http;

    /** @var TweetListener */
    private $listener;

    /** @var SlackClientInterface|ObjectProphecy */
    private $slack;

    /** @var Twitter|ObjectProphecy */
    private $twitter;

    public function setUp(): void
    {
        $this->twitter  = $this->prophesize(Twitter::class);
        $this->slack    = $this->prophesize(SlackClientInterface::class);
        $this->http     = $this->prophesize(HttpClientInterface::class);
        $this->listener = new TweetListener(
            $this->twitter->reveal(),
            $this->slack->reveal(),
            $this->http->reveal()
        );
    }

    public function testFailureToSendTweetWithNoMediaReportsErrorToSlack(): void
    {
        $message          = 'this is the message';
        $responseUrl      = 'http://localhost:9000/api/slack';
        $exceptionMessage = 'this is the error message';
        $tweet            = new Tweet($message, null, $responseUrl);
        $exception        = new RuntimeException($exceptionMessage);

        $this->twitter
            ->statusesUpdate($message)
            ->willThrow($exception)
            ->shouldBeCalled();

        $this->slack
            ->sendWebhookMessage(
                $responseUrl,
                Argument::that(function ($slackMessage) use ($exceptionMessage) {
                    TestCase::assertInstanceOf(SlashResponseMessage::class, $slackMessage);
                    TestCase::assertStringContainsString($exceptionMessage, $slackMessage->getText());

                    return $slackMessage;
                })
            )
            ->shouldBeCalled();

        $this->assertNull($this->listener->__invoke($tweet));
    }

    public function testSendingTweetWithNoMediaReportsSuccessToSlack(): void
    {
        $message     = 'this is the message';
        $responseUrl = 'http://localhost:9000/api/slack';
        $tweet       = new Tweet($message, null, $responseUrl);

        $this->twitter
            ->statusesUpdate($message)
            ->shouldBeCalled();

        $this->slack
            ->sendWebhookMessage(
                $responseUrl,
                Argument::that(function ($slackMessage) use ($message) {
                    TestCase::assertInstanceOf(SlashResponseMessage::class, $slackMessage);
                    TestCase::assertStringContainsString($message, $slackMessage->getText());

                    return $slackMessage;
                })
            )
            ->shouldBeCalled();

        $this->assertNull($this->listener->__invoke($tweet));
    }

    public function testInvalidMediaTypeReportsErrorToSlack(): void
    {
        $message     = 'this is the message';
        $media       = 'https://getlaminas.org/images/logo/trademark-laminas-144x144.svg';
        $responseUrl = 'http://localhost:9000/api/slack';
        $tweet       = new Tweet($message, $media, $responseUrl);
        $request     = $this->prophesize(RequestInterface::class)->reveal();
        $response    = $this->prophesize(ResponseInterface::class);

        $response
            ->getHeaderLine('Content-Type')
            ->willReturn('image/svg+xml')
            ->shouldBeCalled();

        $this->http
            ->createRequest('HEAD', $media)
            ->willReturn($request)
            ->shouldBeCalled();

        $this->http
            ->send($request)
            ->will([$response, 'reveal'])
            ->shouldBeCalled();

        $this->slack
            ->sendWebhookMessage(
                $responseUrl,
                Argument::that(function ($slackMessage) {
                    TestCase::assertInstanceOf(SlashResponseMessage::class, $slackMessage);
                    TestCase::assertStringContainsString('content type', $slackMessage->getText());

                    return $slackMessage;
                })
            )
            ->shouldBeCalled();

        $this->assertNull($this->listener->__invoke($tweet));
    }

    public function testFailureToFetchRemoteMediaReportsErrorToSlack(): void
    {
        $message       = 'this is the message';
        $media         = 'https://getlaminas.org/images/logo/trademark-laminas-144x144.png';
        $responseUrl   = 'http://localhost:9000/api/slack';
        $tweet         = new Tweet($message, $media, $responseUrl);
        $headRequest   = $this->prophesize(RequestInterface::class)->reveal();
        $imageRequest  = $this->prophesize(RequestInterface::class)->reveal();
        $headResponse  = $this->prophesize(ResponseInterface::class);
        $imageResponse = $this->prophesize(ResponseInterface::class);

        $headResponse
            ->getHeaderLine('Content-Type')
            ->willReturn('image/png')
            ->shouldBeCalled();

        $this->http
            ->createRequest('HEAD', $media)
            ->willReturn($headRequest)
            ->shouldBeCalled();

        $this->http
            ->send($headRequest)
            ->will([$headResponse, 'reveal'])
            ->shouldBeCalled();

        $imageResponse
            ->getStatusCode()
            ->willReturn(404)
            ->shouldBeCalled();

        $this->http
            ->createRequest('GET', $media)
            ->willReturn($imageRequest)
            ->shouldBeCalled();

        $this->http
            ->send($imageRequest)
            ->will([$imageResponse, 'reveal'])
            ->shouldBeCalled();

        $this->slack
            ->sendWebhookMessage(
                $responseUrl,
                Argument::that(function ($slackMessage) {
                    TestCase::assertInstanceOf(SlashResponseMessage::class, $slackMessage);
                    TestCase::assertStringContainsString('Unable to fetch media', $slackMessage->getText());

                    return $slackMessage;
                })
            )
            ->shouldBeCalled();

        $this->assertNull($this->listener->__invoke($tweet));
    }

    public function testFailureToUploadMediaToTwitterReportsErrorToSlack(): void
    {
        $message           = 'this is the message';
        $media             = 'https://getlaminas.org/images/logo/trademark-laminas-144x144.png';
        $responseUrl       = 'http://localhost:9000/api/slack';
        $tweet             = new Tweet($message, $media, $responseUrl);
        $headRequest       = $this->prophesize(RequestInterface::class)->reveal();
        $imageRequest      = $this->prophesize(RequestInterface::class)->reveal();
        $headResponse      = $this->prophesize(ResponseInterface::class);
        $imageResponse     = $this->prophesize(ResponseInterface::class);
        $imageBody         = $this->prophesize(StreamInterface::class);
        $twitterHttpClient = $this->prophesize(TwitterHttpClient::class)->reveal();
        $exceptionMessage  = 'Upload error message';
        $exception         = new RuntimeException($exceptionMessage);

        $headResponse
            ->getHeaderLine('Content-Type')
            ->willReturn('image/png')
            ->shouldBeCalled();

        $this->http
            ->createRequest('HEAD', $media)
            ->willReturn($headRequest)
            ->shouldBeCalled();

        $this->http
            ->send($headRequest)
            ->will([$headResponse, 'reveal'])
            ->shouldBeCalled();

        $imageResponse
            ->getStatusCode()
            ->willReturn(200)
            ->shouldBeCalled();

        $imageResponse
            ->getBody()
            ->will([$imageBody, 'reveal'])
            ->shouldBeCalled();

        $imageBody
            ->__toString()
            ->willReturn('image contents')
            ->shouldBeCalled();

        $this->http
            ->createRequest('GET', $media)
            ->willReturn($imageRequest)
            ->shouldBeCalled();

        $this->http
            ->send($imageRequest)
            ->will([$imageResponse, 'reveal'])
            ->shouldBeCalled();

        $this->twitter->getHttpClient()->willReturn($twitterHttpClient)->shouldBeCalled();

        $this->listener->filePopulator = function (string $filename, string $contents) {
            TestCase::assertStringContainsString('trademark-laminas-144x144.png', $filename);
            TestCase::assertSame('image contents', $contents);
        };

        $image = $this->prophesize(Image::class);
        $image->upload($twitterHttpClient)->willThrow($exception)->shouldBeCalled();
        $this->listener->imageFactory = function (string $filename, string $mediaType) use ($image) {
            TestCase::assertStringContainsString('trademark-laminas-144x144.png', $filename);
            TestCase::assertSame('image/png', $mediaType);
            return $image->reveal();
        };

        $this->slack
            ->sendWebhookMessage(
                $responseUrl,
                Argument::that(function ($slackMessage) {
                    TestCase::assertInstanceOf(SlashResponseMessage::class, $slackMessage);
                    TestCase::assertStringContainsString('Unable to upload media', $slackMessage->getText());

                    return $slackMessage;
                })
            )
            ->shouldBeCalled();

        $this->assertNull($this->listener->__invoke($tweet));
    }

    public function testFailureToSendTweetWithMediaReportsErrorToSlack(): void
    {
        $message           = 'this is the message';
        $media             = 'https://getlaminas.org/images/logo/trademark-laminas-144x144.png';
        $responseUrl       = 'http://localhost:9000/api/slack';
        $tweet             = new Tweet($message, $media, $responseUrl);
        $headRequest       = $this->prophesize(RequestInterface::class)->reveal();
        $imageRequest      = $this->prophesize(RequestInterface::class)->reveal();
        $headResponse      = $this->prophesize(ResponseInterface::class);
        $imageResponse     = $this->prophesize(ResponseInterface::class);
        $imageBody         = $this->prophesize(StreamInterface::class);
        $twitterHttpClient = $this->prophesize(TwitterHttpClient::class)->reveal();
        $mediaId           = 'some-hex-id';
        $exceptionMessage  = 'this is the error message';
        $exception         = new RuntimeException($exceptionMessage);

        $headResponse
            ->getHeaderLine('Content-Type')
            ->willReturn('image/png')
            ->shouldBeCalled();

        $this->http
            ->createRequest('HEAD', $media)
            ->willReturn($headRequest)
            ->shouldBeCalled();

        $this->http
            ->send($headRequest)
            ->will([$headResponse, 'reveal'])
            ->shouldBeCalled();

        $imageResponse
            ->getStatusCode()
            ->willReturn(200)
            ->shouldBeCalled();

        $imageResponse
            ->getBody()
            ->will([$imageBody, 'reveal'])
            ->shouldBeCalled();

        $imageBody
            ->__toString()
            ->willReturn('image contents')
            ->shouldBeCalled();

        $this->http
            ->createRequest('GET', $media)
            ->willReturn($imageRequest)
            ->shouldBeCalled();

        $this->http
            ->send($imageRequest)
            ->will([$imageResponse, 'reveal'])
            ->shouldBeCalled();

        $this->twitter->getHttpClient()->willReturn($twitterHttpClient)->shouldBeCalled();
        $this->twitter
            ->statusesUpdate(
                $message,
                null,
                ['media_ids' => [$mediaId]]
            )
            ->willThrow($exception)
            ->shouldBeCalled();

        $this->listener->filePopulator = function (string $filename, string $contents) {
            TestCase::assertStringContainsString('trademark-laminas-144x144.png', $filename);
            TestCase::assertSame('image contents', $contents);
        };

        $twitterResponse           = new TwitterResponse();
        $twitterResponse->media_id = $mediaId;

        $image = $this->prophesize(Image::class);
        $image->upload($twitterHttpClient)->willReturn($twitterResponse)->shouldBeCalled();
        $this->listener->imageFactory = function (string $filename, string $mediaType) use ($image) {
            TestCase::assertStringContainsString('trademark-laminas-144x144.png', $filename);
            TestCase::assertSame('image/png', $mediaType);
            return $image->reveal();
        };

        $this->slack
            ->sendWebhookMessage(
                $responseUrl,
                Argument::that(function ($slackMessage) use ($exceptionMessage) {
                    TestCase::assertInstanceOf(SlashResponseMessage::class, $slackMessage);
                    TestCase::assertStringContainsString($exceptionMessage, $slackMessage->getText());

                    return $slackMessage;
                })
            )
            ->shouldBeCalled();

        $this->assertNull($this->listener->__invoke($tweet));
    }

    public function testSendingTweetWithMediaReportsSuccessToSlack(): void
    {
        $message           = 'this is the message';
        $media             = 'https://getlaminas.org/images/logo/trademark-laminas-144x144.png';
        $responseUrl       = 'http://localhost:9000/api/slack';
        $tweet             = new Tweet($message, $media, $responseUrl);
        $headRequest       = $this->prophesize(RequestInterface::class)->reveal();
        $imageRequest      = $this->prophesize(RequestInterface::class)->reveal();
        $headResponse      = $this->prophesize(ResponseInterface::class);
        $imageResponse     = $this->prophesize(ResponseInterface::class);
        $imageBody         = $this->prophesize(StreamInterface::class);
        $twitterHttpClient = $this->prophesize(TwitterHttpClient::class)->reveal();
        $mediaId           = 'some-hex-id';

        $headResponse
            ->getHeaderLine('Content-Type')
            ->willReturn('image/png')
            ->shouldBeCalled();

        $this->http
            ->createRequest('HEAD', $media)
            ->willReturn($headRequest)
            ->shouldBeCalled();

        $this->http
            ->send($headRequest)
            ->will([$headResponse, 'reveal'])
            ->shouldBeCalled();

        $imageResponse
            ->getStatusCode()
            ->willReturn(200)
            ->shouldBeCalled();

        $imageResponse
            ->getBody()
            ->will([$imageBody, 'reveal'])
            ->shouldBeCalled();

        $imageBody
            ->__toString()
            ->willReturn('image contents')
            ->shouldBeCalled();

        $this->http
            ->createRequest('GET', $media)
            ->willReturn($imageRequest)
            ->shouldBeCalled();

        $this->http
            ->send($imageRequest)
            ->will([$imageResponse, 'reveal'])
            ->shouldBeCalled();

        $this->twitter->getHttpClient()->willReturn($twitterHttpClient)->shouldBeCalled();
        $this->twitter
            ->statusesUpdate(
                $message,
                null,
                ['media_ids' => [$mediaId]]
            )
            ->shouldBeCalled();

        $this->listener->filePopulator = function (string $filename, string $contents) {
            TestCase::assertStringContainsString('trademark-laminas-144x144.png', $filename);
            TestCase::assertSame('image contents', $contents);
        };

        $twitterResponse           = new TwitterResponse();
        $twitterResponse->media_id = $mediaId;

        $image = $this->prophesize(Image::class);
        $image->upload($twitterHttpClient)->willReturn($twitterResponse)->shouldBeCalled();
        $this->listener->imageFactory = function (string $filename, string $mediaType) use ($image) {
            TestCase::assertStringContainsString('trademark-laminas-144x144.png', $filename);
            TestCase::assertSame('image/png', $mediaType);
            return $image->reveal();
        };

        $this->slack
            ->sendWebhookMessage(
                $responseUrl,
                Argument::that(function ($slackMessage) {
                    TestCase::assertInstanceOf(SlashResponseMessage::class, $slackMessage);
                    TestCase::assertStringContainsString('Tweet sent', $slackMessage->getText());

                    return $slackMessage;
                })
            )
            ->shouldBeCalled();

        $this->assertNull($this->listener->__invoke($tweet));
    }
}
