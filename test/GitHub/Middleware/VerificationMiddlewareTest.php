<?php

declare(strict_types=1);

namespace AppTest\GitHub\Middleware;

use App\GitHub\Middleware\VerificationMiddleware;
use Mezzio\ProblemDetails\ProblemDetailsResponseFactory;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function hash_hmac;
use function sprintf;

class VerificationMiddlewareTest extends TestCase
{
    use ProphecyTrait;

    /** @var ServerRequestInterface|ObjectProphecy */
    private $request;

    /** @var RequestHandlerInterface|ObjectProphecy */
    private $handler;
    /** @var ProblemDetailsResponseFactory&ObjectProphecy */
    private $responseFactory;

    protected function setUp(): void
    {
        $this->request         = $this->prophesize(ServerRequestInterface::class);
        $this->handler         = $this->prophesize(RequestHandlerInterface::class);
        $this->responseFactory = $this->prophesize(ProblemDetailsResponseFactory::class);
    }

    public function testMissingPayloadSignatureThrowsException(): void
    {
        $this->request->getHeaderLine('X-Hub-Signature')->willReturn('');

        $middleware = new VerificationMiddleware('', $this->responseFactory->reveal());

        $response = $this->prophesize(ResponseInterface::class)->reveal();
        $this->responseFactory
            ->createResponse($this->request->reveal(), 400, 'No GitHub payload signature headers present')
            ->willReturn($response)
            ->shouldBeCalled();

        $this->assertSame($response, $middleware->process($this->request->reveal(), $this->handler->reveal()));
    }

    public function testInvalidSignatureException(): void
    {
        $this->request->getHeaderLine('X-Hub-Signature')->willReturn('md5');

        $middleware = new VerificationMiddleware('', $this->responseFactory->reveal());

        $response = $this->prophesize(ResponseInterface::class)->reveal();
        $this->responseFactory
            ->createResponse($this->request->reveal(), 400, 'Invalid payload signature')
            ->willReturn($response)
            ->shouldBeCalled();

        $this->assertSame($response, $middleware->process($this->request->reveal(), $this->handler->reveal()));
    }

    public function testInvalidSignatureAlgorithmThrowsException(): void
    {
        $this->request->getHeaderLine('X-Hub-Signature')->willReturn('md5=foo');

        $middleware = new VerificationMiddleware('', $this->responseFactory->reveal());

        $response = $this->prophesize(ResponseInterface::class)->reveal();
        $this->responseFactory
            ->createResponse($this->request->reveal(), 400, 'X-Hub-Signature contains invalid algorithm')
            ->willReturn($response)
            ->shouldBeCalled();

        $this->assertSame($response, $middleware->process($this->request->reveal(), $this->handler->reveal()));
    }

    public function testInvalidSignatureMatchThrowsException(): void
    {
        $stream = $this->prophesize(StreamInterface::class);
        $stream->__toString()->willReturn('{"foo":"bar"}');

        $this->request->getHeaderLine('X-Hub-Signature')->willReturn('sha1=foo');
        $this->request->getBody()->willReturn($stream->reveal());

        $this->handler->handle($this->request->reveal())->shouldNotBeCalled();

        $middleware = new VerificationMiddleware('bar', $this->responseFactory->reveal());

        $response = $this->prophesize(ResponseInterface::class)->reveal();
        $this->responseFactory
            ->createResponse($this->request->reveal(), 400, 'X-Hub-Signature does not match payload signature')
            ->willReturn($response)
            ->shouldBeCalled();

        $this->assertSame($response, $middleware->process($this->request->reveal(), $this->handler->reveal()));
    }

    public function testCallsHandlerWhenVerified(): void
    {
        $secret  = 'bar';
        $algo    = 'sha1';
        $payload = '{"foo":"bar"}';

        $stream = $this->prophesize(StreamInterface::class);
        $stream->__toString()->willReturn($payload);

        $this->request->getHeaderLine('X-Hub-Signature')->willReturn(sprintf(
            '%s=%s',
            $algo,
            hash_hmac($algo, $payload, $secret)
        ));
        $this->request->getBody()->willReturn($stream->reveal());

        $this->handler->handle($this->request->reveal())->shouldBeCalled();

        $middleware = new VerificationMiddleware($secret, $this->responseFactory->reveal());
        $response   = $middleware->process($this->request->reveal(), $this->handler->reveal());

        self::assertInstanceOf(ResponseInterface::class, $response);
    }
}
