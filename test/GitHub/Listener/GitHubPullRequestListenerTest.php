<?php

declare(strict_types=1);

namespace AppTest\GitHub\Listener;

use App\GitHub\Event\GitHubPullRequest;
use App\GitHub\Listener\GitHubPullRequestListener;
use App\Slack\Domain\ContextBlock;
use App\Slack\Domain\SectionBlock;
use App\Slack\Domain\TextObject;
use App\Slack\Domain\WebAPIMessage;
use App\Slack\MarkdownToSlackFormatter;
use App\Slack\Response\SlackResponseInterface;
use App\Slack\SlackClientInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

use function file_get_contents;
use function json_decode;

class GitHubPullRequestListenerTest extends TestCase
{
    use ProphecyTrait;

    public function testSendsNotificationToSlackBasedOnPullRequest(): void
    {
        $json    = file_get_contents(__DIR__ . '/../../Fixtures/pull-request-closed.json');
        $payload = json_decode($json, true);
        $comment = new GitHubPullRequest($payload);

        $response = $this->prophesize(SlackResponseInterface::class)->reveal();

        $slack = $this->prophesize(SlackClientInterface::class);
        $slack
            ->sendWebAPIMessage(Argument::that(function ($message) use ($payload) {
                TestCase::assertInstanceOf(WebAPIMessage::class, $message);

                TestCase::assertSame('#github', $message->getChannel());

                $text = $message->getText();
                TestCase::assertStringContainsString($payload['repository']['full_name'], $text);
                TestCase::assertStringContainsString($payload['sender']['login'], $text);
                TestCase::assertStringContainsString($payload['pull_request']['html_url'], $text);

                $blocks = $message->getBlocks();
                TestCase::assertCount(2, $blocks);

                $context = $blocks[0];
                TestCase::assertInstanceOf(ContextBlock::class, $context);
                TestCase::assertCount(3, $context->getElements());
                $summary = $context->getElements()[2];
                TestCase::assertInstanceOf(TextObject::class, $summary);
                TestCase::assertSame(TextObject::TYPE_MARKDOWN, $summary->toArray()['type']);

                $fields = $blocks[1];
                TestCase::assertInstanceOf(SectionBlock::class, $fields);
                TestCase::assertCount(3, $fields->getFields());
                TestCase::assertStringContainsString('merged', $fields->getFields()[2]->toArray()['text']);

                return $message;
            }))
            ->willReturn($response)
            ->shouldBeCalled();

        $listener = new GitHubPullRequestListener('github', $slack->reveal());

        $this->assertNull($listener($comment));
    }

    public function testOpeningPullRequsetSendsNotificationToSlackWithExtraBlock(): void
    {
        $json    = file_get_contents(__DIR__ . '/../../Fixtures/pull-request-opened.json');
        $payload = json_decode($json, true);
        $comment = new GitHubPullRequest($payload);

        $response = $this->prophesize(SlackResponseInterface::class)->reveal();

        $slack = $this->prophesize(SlackClientInterface::class);
        $slack
            ->sendWebAPIMessage(Argument::that(function ($message) use ($payload) {
                TestCase::assertInstanceOf(WebAPIMessage::class, $message);

                TestCase::assertSame('#github', $message->getChannel());

                $text = $message->getText();
                TestCase::assertStringContainsString($payload['repository']['full_name'], $text);
                TestCase::assertStringContainsString($payload['sender']['login'], $text);
                TestCase::assertStringContainsString($payload['pull_request']['html_url'], $text);

                $blocks = $message->getBlocks();
                TestCase::assertCount(3, $blocks);

                $context = $blocks[0];
                TestCase::assertInstanceOf(ContextBlock::class, $context);
                TestCase::assertCount(3, $context->getElements());
                $summary = $context->getElements()[2];
                TestCase::assertInstanceOf(TextObject::class, $summary);
                TestCase::assertSame(TextObject::TYPE_MARKDOWN, $summary->toArray()['type']);

                $body = $blocks[1];
                TestCase::assertInstanceOf(SectionBlock::class, $body);
                TestCase::assertSame(
                    (new MarkdownToSlackFormatter())->format($payload['pull_request']['body']),
                    $body->getText()->toArray()['text']
                );

                $fields = $blocks[2];
                TestCase::assertInstanceOf(SectionBlock::class, $fields);
                TestCase::assertCount(3, $fields->getFields());

                return $message;
            }))
            ->willReturn($response)
            ->shouldBeCalled();

        $listener = new GitHubPullRequestListener('github', $slack->reveal());

        $this->assertNull($listener($comment));
    }
}
