<?php

declare(strict_types=1);

namespace AppTest\GitHub\Middleware;

use App\GitHub\Middleware\VerificationMiddleware;
use DomainException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Server\RequestHandlerInterface;
use function hash_hmac;
use function sprintf;

class VerificationMiddlewareTest extends TestCase
{
    /** @var ServerRequestInterface|ObjectProphecy */
    private $request;

    /** @var RequestHandlerInterface|ObjectProphecy */
    private $handler;

    protected function setUp() : void
    {
        $this->request = $this->prophesize(ServerRequestInterface::class);
        $this->handler = $this->prophesize(RequestHandlerInterface::class);
    }

    public function testMissingPayloadSignatureThrowsException() : void
    {
        $this->request->getHeaderLine('X-Hub-Signature')->willReturn('');

        $middleware = new VerificationMiddleware('');

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('No GitHub payload signature headers present');
        $middleware->process($this->request->reveal(), $this->handler->reveal());
    }

    public function testInvalidSignatureException() : void
    {
        $this->request->getHeaderLine('X-Hub-Signature')->willReturn('md5');

        $middleware = new VerificationMiddleware('');

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Invalid payload signature');
        $middleware->process($this->request->reveal(), $this->handler->reveal());
    }

    public function testInvalidSignatureAlgorithmThrowsException() : void
    {
        $this->request->getHeaderLine('X-Hub-Signature')->willReturn('md5=foo');

        $middleware = new VerificationMiddleware('');

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('X-Hub-Signature contains invalid algorithm');
        $middleware->process($this->request->reveal(), $this->handler->reveal());
    }

    public function testInvalidSignatureMatchThrowsException() : void
    {
        $stream = $this->prophesize(StreamInterface::class);
        $stream->__toString()->willReturn('{"foo":"bar"}');

        $this->request->getHeaderLine('X-Hub-Signature')->willReturn('sha1=foo');
        $this->request->getBody()->willReturn($stream->reveal());

        $this->handler->handle($this->request->reveal())->shouldNotBeCalled();

        $middleware = new VerificationMiddleware('bar');

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('X-Hub-Signature does not match payload signature');
        $middleware->process($this->request->reveal(), $this->handler->reveal());
    }

    public function testCallsHandlerWhenVerified() : void
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

        $middleware = new VerificationMiddleware($secret);
        $response   = $middleware->process($this->request->reveal(), $this->handler->reveal());

        self::assertInstanceOf(ResponseInterface::class, $response);
    }
}
