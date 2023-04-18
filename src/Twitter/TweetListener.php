<?php

declare(strict_types=1);

namespace App\Twitter;

use App\Slack\Domain\ContextBlock;
use App\Slack\Domain\DividerBlock;
use App\Slack\Domain\SectionBlock;
use App\Slack\Domain\TextObject;
use App\Slack\Domain\WebAPIMessage;
use App\Slack\SlackClientInterface;

use function preg_replace;
use function sprintf;

class TweetListener
{
    public const DEFAULT_SLACK_CHANNEL = 'news';

    /** @var SlackClientInterface */
    private $slack;
    private string $channel;

    public function __construct(SlackClientInterface $slack, string $channel = self::DEFAULT_SLACK_CHANNEL)
    {
        $this->slack   = $slack;
        $this->channel = $channel;
    }

    public function __invoke(Tweet $tweet): void
    {
        $message = $this->createMessage($tweet);
        $this->slack->sendWebAPIMessage($message);
    }

    private function createMessage(Tweet $tweet): WebAPIMessage
    {
        $dateTime = $tweet->timestamp();
        $content  = $tweet->message();
        $message  = new WebAPIMessage();

        $message->setChannel($this->channel);
        $message->setText(sprintf('Tweet from @getlaminas: %s', $content));
        $message->addBlock(new DividerBlock());
        $message->addBlock(ContextBlock::fromArray([
            'elements' => [
                [
                    'type'      => 'image',
                    'image_url' => 'https://laminas.dev/img/twitter-icon.png',
                    'alt_text'  => 'Twitter',
                ],
                [
                    'type' => TextObject::TYPE_MARKDOWN,
                    'text' => '<https://twitter.com/getlaminas|@getlaminas on Twitter>',
                ],
                [
                    'type' => TextObject::TYPE_MARKDOWN,
                    'text' => sprintf(
                        '<!date^%d^{date} at {time}|%s>',
                        $dateTime->getTimestamp(),
                        $dateTime->format('Y-m-d H:i e')
                    ),
                ],
            ],
        ]));
        $message->addBlock(SectionBlock::fromArray([
            'text'   => [
                'type'     => TextObject::TYPE_MARKDOWN,
                'text'     => $this->formatMessage($content),
                'verbatim' => false,
            ],
            'fields' => [
                [
                    'type' => TextObject::TYPE_MARKDOWN,
                    'text' => sprintf(
                        '<%s|%s>',
                        $tweet->url(),
                        $dateTime->format('Y-m-d H:i e')
                    ),
                ],
            ],
        ]));

        return $message;
    }

    private function formatMessage(string $content): string
    {
        // Replace name references
        $content = preg_replace(
            '/\W@(.*?)\W/',
            '<https://twitter.com/\1|@\1>',
            $content
        );

        // Replace hashtag references
        $content = preg_replace(
            '/\W(#.*?)\W/',
            '<https://twitter.com/search?q=\1|\1>',
            $content
        );

        return $content;
    }
}
