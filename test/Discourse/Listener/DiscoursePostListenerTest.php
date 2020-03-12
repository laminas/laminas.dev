<?php

declare(strict_types=1);

namespace AppTest\Discourse\Listener;

use App\Discourse\Event\DiscoursePost;
use App\Discourse\Listener\DiscoursePostListener;
use App\Slack\Domain\WebAPIMessage;
use App\Slack\Response\SlackResponseInterface;
use App\Slack\SlackClientInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

use function time;

class DiscoursePostListenerTest extends TestCase
{
    public function invalidPosts(): iterable
    {
        $timestamp = time();

        yield 'missing-post' => [[]];
        yield 'hidden-post' => [['post' => ['hidden' => true]]];
        yield 'deleted_post' => [['post' => ['deleted_at' => $timestamp]]];
        yield 'edited_post' => [['post' => ['created_at' => $timestamp, 'updated_at' => $timestamp + 1]]];
    }

    /** @dataProvider invalidPosts */
    public function testReturnsWithoutCallingSlackAPIWhenInvalid(array $payload): void
    {
        $slack = $this->prophesize(SlackClientInterface::class);
        $slack->sendWebAPIMessage(Argument::any())->shouldNotBeCalled();

        $post     = new DiscoursePost('general', $payload, '');
        $listener = new DiscoursePostListener($slack->reveal());

        $this->assertNull($listener($post));
    }

    public function testSendsRequestToSlackApiUsingPostDetailsWhenPostIsValid(): void
    {
        $timestamp = time();
        $response  = $this->prophesize(SlackResponseInterface::class)->reveal();

        $post = new DiscoursePost('general', [
            'post' => [
                'hidden'      => false,
                'created_at'  => $timestamp,
                'updated_at'  => $timestamp,
                'deleted_at'  => null,
                'topic_slug'  => 'how-to-do-something',
                'topic_id'    => 11111111,
                'id'          => 5,
                'topic_title' => 'How to do something',
                'username'    => 'somebody',
                'name'        => 'Some Body',
                'cooked'      => 'The HTML formatted content',
            ],
        ], 'https://discourse.laminas.dev');

        $slack = $this->prophesize(SlackClientInterface::class);
        $slack
            ->sendWebAPIMessage(Argument::that(function ($message) {
                TestCase::assertInstanceOf(WebAPIMessage::class, $message);

                $rendered = $message->toArray();

                TestCase::assertArrayHasKey('channel', $rendered);
                TestCase::assertSame('#general', $rendered['channel']);

                TestCase::assertArrayHasKey('text', $rendered);
                TestCase::assertStringContainsString(
                    'Comment created for How to do something: https://discourse.laminas.dev/',
                    $rendered['text']
                );

                TestCase::assertArrayHasKey('blocks', $rendered);
                TestCase::assertCount(4, $rendered['blocks']);

                TestCase::assertSame('divider', $rendered['blocks'][0]['type']);
                TestCase::assertSame('context', $rendered['blocks'][1]['type']);
                TestCase::assertSame('section', $rendered['blocks'][2]['type']);
                TestCase::assertArrayHasKey('text', $rendered['blocks'][2]);
                TestCase::assertSame('section', $rendered['blocks'][3]['type']);
                TestCase::assertArrayHasKey('fields', $rendered['blocks'][3]);
                TestCase::assertArrayNotHasKey('text', $rendered['blocks'][3]);

                return $message;
            }))
            ->willReturn($response)
            ->shouldBeCalled();

        $listener = new DiscoursePostListener($slack->reveal());

        $this->assertNull($listener($post));
    }
}
