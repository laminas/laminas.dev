<?php

declare(strict_types=1);

namespace AppTest\Slack\Listener;

use App\Slack\Domain\SlashResponseMessage;
use App\Slack\Event\Retweet;
use App\Slack\Listener\RetweetListener;
use App\Slack\SlackClientInterface;
use Laminas\Twitter\Twitter;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use RuntimeException;

use function sprintf;

class RetweetListenerTest extends TestCase
{
    use ProphecyTrait;

    /** @var RetweetListener */
    private $listener;

    /** @var SlackClientInterface|ObjectProphecy */
    private $slack;

    /** @var Twitter|ObjectProphecy */
    private $twitter;

    public function setUp(): void
    {
        $this->twitter  = $this->prophesize(Twitter::class);
        $this->slack    = $this->prophesize(SlackClientInterface::class);
        $this->listener = new RetweetListener(
            $this->twitter->reveal(),
            $this->slack->reveal()
        );
    }

    public function testReportsRetweetErrorToSlack(): void
    {
        $url         = 'https://twitter.com/getlaminas/status/1239539812941651968';
        $id          = '1239539812941651968';
        $responseUrl = 'http://localhost:9000/api/slack';
        $retweet     = new Retweet($url, $responseUrl);
        $message     = 'error message';
        $exception   = new RuntimeException($message);

        $this->twitter
            ->post(sprintf('statuses/retweet/%s', $id))
            ->willThrow($exception)
            ->shouldBeCalled();

        // phpcs:disable SlevomatCodingStandard.Commenting.InlineDocCommentDeclaration.MissingVariable
        $this->slack
            ->sendWebhookMessage(
                $responseUrl,
                Argument::that(function ($slackMessage) use ($message) {
                    TestCase::assertInstanceOf(SlashResponseMessage::class, $slackMessage);
                    /** @var SlashResponseMessage $slackMessage */
                    TestCase::assertStringContainsString($message, $slackMessage->getText());

                    return $slackMessage;
                })
            )
            ->shouldBeCalled();
        // phpcs:enable SlevomatCodingStandard.Commenting.InlineDocCommentDeclaration.MissingVariable

        $this->assertNull($this->listener->__invoke($retweet));
    }

    public function testReportsRetweetSuccessToSlack(): void
    {
        $url         = 'https://twitter.com/getlaminas/status/1239539812941651968';
        $id          = '1239539812941651968';
        $responseUrl = 'http://localhost:9000/api/slack';
        $retweet     = new Retweet($url, $responseUrl);

        $this->twitter
            ->post(sprintf('statuses/retweet/%s', $id))
            ->shouldBeCalled();

        // phpcs:disable SlevomatCodingStandard.Commenting.InlineDocCommentDeclaration.MissingVariable
        $this->slack
            ->sendWebhookMessage(
                $responseUrl,
                Argument::that(function ($slackMessage) {
                    TestCase::assertInstanceOf(SlashResponseMessage::class, $slackMessage);
                    /** @var SlashResponseMessage $slackMessage */
                    TestCase::assertSame('Retweet sent', $slackMessage->getText());

                    return $slackMessage;
                })
            )
            ->shouldBeCalled();
        // phpcs:enable SlevomatCodingStandard.Commenting.InlineDocCommentDeclaration.MissingVariable

        $this->assertNull($this->listener->__invoke($retweet));
    }
}
