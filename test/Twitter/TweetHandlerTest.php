<?php

declare(strict_types=1);

namespace AppTest\Twitter;

use App\Twitter\Tweet;
use App\Twitter\TweetHandler;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class TweetHandlerTest extends TestCase
{
    public function testDispatchesTweetBasedOnBodyParamsAndReturnsEmptyResponse(): void
    {
        $body = [
            'text'      => 'This is the message',
            'url'       => 'https://twitter.com/getlaminas/status/1240620908454326274',
            'timestamp' => 'March 19, 2020 at 11:29AM',
        ];

        $dispatcher      = $this->prophesize(EventDispatcherInterface::class);
        $request         = $this->prophesize(ServerRequestInterface::class);
        $response        = $this->prophesize(ResponseInterface::class)->reveal();
        $responseFactory = $this->prophesize(ResponseFactoryInterface::class);

        $request->getParsedBody()->willReturn($body)->shouldBeCalled();
        $dispatcher
            ->dispatch(Argument::that(function ($tweet) use ($body) {
                TestCase::assertInstanceOf(Tweet::class, $tweet);
                /** @var Tweet $tweet */
                TestCase::assertSame($body['text'], $tweet->message());
                TestCase::assertSame($body['url'], $tweet->url());
                TestCase::assertEquals(new DateTimeImmutable('March 19, 2020 11:29AM'), $tweet->timestamp());

                return $tweet;
            }))
            ->shouldBeCalled();
        $responseFactory->createResponse(202)->willReturn($response)->shouldBeCalled();

        $handler = new TweetHandler($responseFactory->reveal(), $dispatcher->reveal());

        $this->assertSame($response, $handler->handle($request->reveal()));
    }
}
