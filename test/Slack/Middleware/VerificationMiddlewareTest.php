<?php

declare(strict_types=1);

namespace AppTest\Slack\Middleware;

use App\Slack\Middleware\VerificationMiddleware;
use Mezzio\ProblemDetails\ProblemDetailsResponseFactory;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function hash_hmac;
use function http_build_query;
use function ini_get;
use function sprintf;
use function time;

use const PHP_QUERY_RFC3986;

class VerificationMiddlewareTest extends TestCase
{
    use ProphecyTrait;

    /** @var RequestHandlerInterface|ObjectProphecy */
    private $handler;

    /** @var ProblemDetailsResponseFactory|ObjectProphecy */
    private $responseFactory;

    /** @var string */
    private $secret;

    public function setUp(): void
    {
        $this->handler         = $this->prophesize(RequestHandlerInterface::class);
        $this->responseFactory = $this->prophesize(ProblemDetailsResponseFactory::class);
        $this->secret          = 'xoxb-some-secret';
        $this->middleware      = new VerificationMiddleware(
            $this->secret,
            $this->responseFactory->reveal()
        );
    }

    public function testVerificationIsSuccessful(): void
    {
        $timestamp = time();
        $data      = [
            'command' => '/deploy',
            'text'    => '94070',
        ];
        $body      = http_build_query($data, '', ini_get('arg_separator.input'), PHP_QUERY_RFC3986);
        $sig       = hash_hmac('sha256', sprintf('v0:%d:%s', $timestamp, $body), $this->secret);

        $reqBody = $this->prophesize(StreamInterface::class);
        $reqBody->__toString()->willReturn($body)->shouldBeCalled();

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeaderLine('X-Slack-Request-Timestamp')->willReturn((string) $timestamp)->shouldBeCalled();
        $request->getHeaderLine('X-Slack-Signature')->willReturn(sprintf('v0=%s', $sig))->shouldBeCalled();
        $request->getBody()->will([$reqBody, 'reveal']);

        $this->responseFactory->createResponse(Argument::any())->shouldNotBeCalled();

        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handler
            ->handle($request->reveal())
            ->shouldBeCalled()
            ->willReturn($this->prophesize(ResponseInterface::class));

        $response = $this->middleware->process($request->reveal(), $handler->reveal());

        self::assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testReturnsErrorResponseWhenSignatureHeaderMissing(): void
    {
        $response = $this->prophesize(ResponseInterface::class)->reveal();
        $request  = $this->prophesize(ServerRequestInterface::class);
        $request->getHeaderLine('X-Slack-Signature')->willReturn('')->shouldBeCalled();
        $this->responseFactory
            ->createResponse(
                Argument::that([$request, 'reveal']),
                400,
                'Missing signature'
            )
            ->willReturn($response)
            ->shouldBeCalled();

        $this->handler->handle(Argument::any())->shouldNotBeCalled();

        $this->assertSame(
            $response,
            $this->middleware->process($request->reveal(), $this->handler->reveal())
        );
    }

    public function testReturnsErrorResponseWhenSignatureHeaderMalformed(): void
    {
        $response = $this->prophesize(ResponseInterface::class)->reveal();
        $request  = $this->prophesize(ServerRequestInterface::class);
        $request->getHeaderLine('X-Slack-Signature')->willReturn('some-value')->shouldBeCalled();
        $this->responseFactory
            ->createResponse(
                Argument::that([$request, 'reveal']),
                400,
                'Malformed signature'
            )
            ->willReturn($response)
            ->shouldBeCalled();

        $this->handler->handle(Argument::any())->shouldNotBeCalled();

        $this->assertSame(
            $response,
            $this->middleware->process($request->reveal(), $this->handler->reveal())
        );
    }

    public function testReturnsErrorResponseWhenTimestampHeaderMissing(): void
    {
        $response = $this->prophesize(ResponseInterface::class)->reveal();
        $request  = $this->prophesize(ServerRequestInterface::class);
        $request->getHeaderLine('X-Slack-Signature')->willReturn('v0=signature')->shouldBeCalled();
        $request->getHeaderLine('X-Slack-Request-Timestamp')->willReturn('')->shouldBeCalled();
        $this->responseFactory
            ->createResponse(
                Argument::that([$request, 'reveal']),
                400,
                'Missing timestamp'
            )
            ->willReturn($response)
            ->shouldBeCalled();

        $this->handler->handle(Argument::any())->shouldNotBeCalled();

        $this->assertSame(
            $response,
            $this->middleware->process($request->reveal(), $this->handler->reveal())
        );
    }

    public function testReturnsErrorResponseWhenTimestampIsStale(): void
    {
        $timestamp = time() - 600;
        $response  = $this->prophesize(ResponseInterface::class)->reveal();
        $request   = $this->prophesize(ServerRequestInterface::class);
        $request->getHeaderLine('X-Slack-Signature')->willReturn('v0=signature')->shouldBeCalled();
        $request->getHeaderLine('X-Slack-Request-Timestamp')->willReturn((string) $timestamp)->shouldBeCalled();
        $this->responseFactory
            ->createResponse(
                Argument::that([$request, 'reveal']),
                400,
                'Invalid timestamp'
            )
            ->willReturn($response)
            ->shouldBeCalled();

        $this->handler->handle(Argument::any())->shouldNotBeCalled();

        $this->assertSame(
            $response,
            $this->middleware->process($request->reveal(), $this->handler->reveal())
        );
    }

    public function testReturnsErrorResponseWhenSignatureIsInvalid(): void
    {
        $timestamp = time();
        $response  = $this->prophesize(ResponseInterface::class)->reveal();
        $request   = $this->prophesize(ServerRequestInterface::class);
        $body      = $this->prophesize(StreamInterface::class);

        $request->getHeaderLine('X-Slack-Signature')->willReturn('v0=signature')->shouldBeCalled();
        $request->getHeaderLine('X-Slack-Request-Timestamp')->willReturn((string) $timestamp)->shouldBeCalled();
        $request->getBody()->will([$body, 'reveal'])->shouldBeCalled();

        $body->__toString()->willReturn('content')->shouldBeCalled();

        $this->responseFactory
            ->createResponse(
                Argument::that([$request, 'reveal']),
                400,
                'Invalid signature'
            )
            ->willReturn($response)
            ->shouldBeCalled();

        $this->handler->handle(Argument::any())->shouldNotBeCalled();

        $this->assertSame(
            $response,
            $this->middleware->process($request->reveal(), $this->handler->reveal())
        );
    }
}
