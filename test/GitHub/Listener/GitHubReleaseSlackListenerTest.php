<?php

declare(strict_types=1);

namespace AppTest\GitHub\Listener;

use App\GitHub\Event\GitHubRelease;
use App\GitHub\Listener\GitHubReleaseSlackListener;
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

class GitHubReleaseSlackListenerTest extends TestCase
{
    public function testSendsNotificationToSlackBasedOnCommentProvided(): void
    {
        $json    = file_get_contents(__DIR__ . '/../../Fixtures/release-published.json');
        $payload = json_decode($json, true);
        $comment = new GitHubRelease($payload);

        $response = $this->prophesize(SlackResponseInterface::class)->reveal();

        $slack = $this->prophesize(SlackClientInterface::class);
        $slack
            ->sendWebAPIMessage(Argument::that(function ($message) use ($payload) {
                TestCase::assertInstanceOf(WebAPIMessage::class, $message);

                TestCase::assertSame('#github', $message->getChannel());

                $text = $message->getText();
                TestCase::assertStringContainsString($payload['repository']['full_name'], $text);
                TestCase::assertStringContainsString($payload['sender']['login'], $text);
                TestCase::assertStringContainsString($payload['release']['html_url'], $text);

                $blocks = $message->getBlocks();
                TestCase::assertCount(3, $blocks);

                $context = $blocks[0];
                TestCase::assertInstanceOf(ContextBlock::class, $context);
                TestCase::assertCount(3, $context->getElements());
                $text = $context->getElements()[2];
                TestCase::assertInstanceOf(TextObject::class, $text);
                TestCase::assertSame(TextObject::TYPE_MARKDOWN, $text->toArray()['type']);

                $body = $blocks[1];
                TestCase::assertInstanceOf(SectionBlock::class, $body);
                $text = $body->getText();
                TestCase::assertSame($payload['release']['body'], $text->toArray()['text']);

                $fields = $blocks[2];
                TestCase::assertInstanceOf(SectionBlock::class, $fields);
                TestCase::assertCount(2, $fields->getFields());

                return $message;
            }))
            ->willReturn($response)
            ->shouldBeCalled();

        $listener = new GitHubReleaseSlackListener('github', $slack->reveal());

        $this->assertNull($listener($comment));
    }
}
