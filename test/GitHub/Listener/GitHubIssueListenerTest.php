<?php

declare(strict_types=1);

namespace AppTest\GitHub\Listener;

use App\GitHub\Event\GitHubIssue;
use App\GitHub\Listener\GitHubIssueListener;
use App\Slack\Domain\ContextBlock;
use App\Slack\Domain\SectionBlock;
use App\Slack\Domain\TextObject;
use App\Slack\Domain\WebAPIMessage;
use App\Slack\Response\SlackResponseInterface;
use App\Slack\SlackClientInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

use function file_get_contents;
use function json_decode;

class GitHubIssueListenerTest extends TestCase
{
    public function testSendsNotificationToSlackBasedOnIssueProvided(): void
    {
        $json    = file_get_contents(__DIR__ . '/../../Fixtures/issues-opened.json');
        $payload = json_decode($json, true);
        $comment = new GitHubIssue($payload);

        $response = $this->prophesize(SlackResponseInterface::class)->reveal();

        $slack = $this->prophesize(SlackClientInterface::class);
        $slack
            ->sendWebAPIMessage(Argument::that(function ($message) use ($payload) {
                TestCase::assertInstanceOf(WebAPIMessage::class, $message);

                TestCase::assertSame('#github', $message->getChannel());

                $text = $message->getText();
                TestCase::assertStringContainsString($payload['repository']['full_name'], $text);
                TestCase::assertStringContainsString($payload['sender']['login'], $text);
                TestCase::assertStringContainsString($payload['issue']['html_url'], $text);

                $blocks = $message->getBlocks();
                TestCase::assertCount(4, $blocks);

                $context = $blocks[0];
                TestCase::assertInstanceOf(ContextBlock::class, $context);
                TestCase::assertCount(3, $context->getElements());
                $summary = $context->getElements()[2];
                TestCase::assertInstanceOf(TextObject::class, $summary);
                TestCase::assertSame(TextObject::TYPE_MARKDOWN, $summary->toArray()['type']);

                $body = $blocks[1];
                TestCase::assertInstanceOf(SectionBlock::class, $body);
                $text = $body->getText();
                TestCase::assertSame($payload['issue']['body'], $text->toArray()['text']);

                $fields = $blocks[2];
                TestCase::assertInstanceOf(SectionBlock::class, $fields);
                TestCase::assertCount(4, $fields->getFields());

                $fields = $blocks[3];
                TestCase::assertInstanceOf(SectionBlock::class, $fields);
                TestCase::assertCount(3, $fields->getFields());

                return $message;
            }))
            ->willReturn($response)
            ->shouldBeCalled();

        $listener = new GitHubIssueListener('github', $slack->reveal());

        $this->assertNull($listener($comment));
    }
}
