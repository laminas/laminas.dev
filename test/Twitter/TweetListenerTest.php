<?php

declare(strict_types=1);

namespace AppTest\Twitter;

use App\Slack\Domain\ContextBlock;
use App\Slack\Domain\DividerBlock;
use App\Slack\Domain\SectionBlock;
use App\Slack\Domain\WebAPIMessage;
use App\Slack\SlackClientInterface;
use App\Twitter\Tweet;
use App\Twitter\TweetListener;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

use function sprintf;

class TweetListenerTest extends TestCase
{
    use ProphecyTrait;

    /** @var string */
    private $channel;

    /** @var TweetListener */
    private $listener;

    /** @var SlackClientInterface|ObjectProphecy */
    private $slack;

    public function setUp(): void
    {
        $this->channel  = TweetListener::DEFAULT_SLACK_CHANNEL;
        $this->slack    = $this->prophesize(SlackClientInterface::class);
        $this->listener = new TweetListener(
            $this->slack->reveal(),
            $this->channel
        );
    }

    public function testSendsMessageBasedOnTweetViaSlackAPI(): void
    {
        $text  = 'This is a tweet mentioning @A_User and a #hashtag in it.';
        $url   = 'https://twitter.com/getlaminas/status/1240620908454326274';
        $date  = new DateTimeImmutable('2020-03-19T11:29:12-05:00');
        $tweet = new Tweet($text, $url, $date);

        // phpcs:disable SlevomatCodingStandard.Commenting.InlineDocCommentDeclaration.MissingVariable
        $this->slack
            ->sendWebAPIMessage(Argument::that(function ($message) use ($text, $url, $date) {
                TestCase::assertInstanceOf(WebAPIMessage::class, $message);
                /** @var WebAPIMessage $message */
                TestCase::assertSame(sprintf('#%s', TweetListener::DEFAULT_SLACK_CHANNEL), $message->getChannel());
                TestCase::assertStringContainsString($text, $message->getText());

                $blocks = $message->getBlocks();
                TestCase::assertInstanceOf(DividerBlock::class, $blocks[0]);

                $context = $blocks[1];
                TestCase::assertInstanceOf(ContextBlock::class, $context);
                $elements = $context->toArray()['elements'] ?? [];
                TestCase::assertCount(3, $elements);
                TestCase::assertStringContainsString((string) $date->getTimestamp(), $elements[2]['text']);
                TestCase::assertStringContainsString($date->format('Y-m-d H:i e'), $elements[2]['text']);

                $section = $blocks[2];
                TestCase::assertInstanceOf(SectionBlock::class, $section);
                $contents = $section->toArray()['text'];
                TestCase::assertArrayNotHasKey('verbatim', $contents);
                TestCase::assertNotSame($text, $contents['text']);
                TestCase::assertStringContainsString('<https://twitter.com/A_User|@A_User>', $contents['text']);
                TestCase::assertStringContainsString(
                    '<https://twitter.com/search?q=#hashtag|#hashtag>',
                    $contents['text']
                );
                $fields = $section->toArray()['fields'];
                TestCase::assertCount(1, $fields);
                $field = $fields[0]['text'];
                TestCase::assertStringContainsString($url, $field);
                TestCase::assertStringContainsString($date->format('Y-m-d H:i e'), $field);

                return $message;
            }))
            ->shouldBeCalled();
        // phpcs:enable SlevomatCodingStandard.Commenting.InlineDocCommentDeclaration.MissingVariable

        $this->assertNull($this->listener->__invoke($tweet));
    }
}
