<?php

declare(strict_types=1);

namespace AppTest\Discourse\Middleware;

use App\Discourse\Event\DiscoursePost;
use App\Discourse\Middleware\DiscourseHandler;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use function date;

class DiscourseHandlerTest extends TestCase
{
    public function testDispatchesDiscoursePostAndReturnsEmpty202Response(): void
    {
        /** @var ServerRequestInterface|ObjectProphecy $request */
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getAttribute('channel')->willReturn('qanda')->shouldBeCalled();

        $now = date('r');
        $request
            ->getParsedBody()
            ->willReturn([
                'post' => [
                    'topic_slug' => 'some-topic',
                    'topic_id'   => 42,
                    'id'         => 4242,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ])
            ->shouldBeCalled();

        $discourseUrl = 'https://discourse.laminas.dev';

        $response        = $this->prophesize(ResponseInterface::class)->reveal();
        $responseFactory = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactory
            ->createResponse(202)
            ->willReturn($response)
            ->shouldBeCalled();

        $dispatcher = $this->prophesize(EventDispatcherInterface::class);
        $dispatcher
            ->dispatch(Argument::that(function ($post) {
                TestCase::assertInstanceOf(DiscoursePost::class, $post);
                TestCase::assertSame('#qanda', $post->getChannel());
                TestCase::assertSame('https://discourse.laminas.dev/t/some-topic/42/4242', $post->getPostUrl());
                TestCase::assertTrue($post->isValidForSlack());
                return $post;
            }))
            ->shouldBeCalled();

        $handler = new DiscourseHandler(
            $discourseUrl,
            $dispatcher->reveal(),
            $responseFactory->reveal()
        );

        $this->assertSame($response, $handler->handle($request->reveal()));
    }
}
