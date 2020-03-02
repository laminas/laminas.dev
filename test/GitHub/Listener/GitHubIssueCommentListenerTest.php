<?php

declare(strict_types=1);

namespace AppTest\GitHub\Listener;

use App\GitHub\Event\GitHubIssueComment;
use App\GitHub\Listener\GitHubIssueCommentListener;
use App\Slack\Domain\WebAPIMessage;
use App\Slack\Response\SlackResponseInterface;
use App\Slack\SlackClientInterface;
use ArgumentCountError;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

class GitHubIssueCommentListenerTest extends TestCase
{
    public function testSendsNotificationToSlackBasedOnCommentProvided(): void
    {
        $json    = file_get_contents(__DIR__ . '/../../Fixtures/comment-created.json');
        $payload = json_decode($json, true);
        $comment = new GitHubIssueComment($payload);

        $response = $this->prophesize(SlackResponseInterface::class)->reveal();

        $slack = $this->prophesize(SlackClientInterface::class);
        $slack
            ->sendWebAPIMessage(Argument::that(function ($message) use ($payload) {
                TestCase::assertInstanceOf(WebAPIMessage::class, $message);

                $contents = $message->toArray();

                TestCase::assertArrayHasKey('channel', $contents);
                TestCase::assertSame('#github', $contents['channel']);

                TestCase::assertArrayHasKey('text', $contents);
                TestCase::assertStringContainsString($payload['repository']['full_name'], $contents['text']);
                TestCase::assertStringContainsString($payload['sender']['login'], $contents['text']);
                TestCase::assertStringContainsString($payload['issue']['title'], $contents['text']);
                TestCase::assertStringContainsString($payload['comment']['html_url'], $contents['text']);

                TestCase::assertArrayHasKey('blocks', $contents);
                TestCase::assertCount(4, $contents['blocks']);

                return $message;
            }))
            ->willReturn($response)
            ->shouldBeCalled();

        $listener = new GitHubIssueCommentListener('github', $slack->reveal());

        $this->assertNull($listener($comment));
    }
}
