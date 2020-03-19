<?php

declare(strict_types=1);

namespace App\Slack\Listener;

use App\Slack\Domain\SlashResponseMessage;
use App\Slack\Event\TwitterReply;
use App\Slack\SlackClientInterface;
use Laminas\Twitter\Twitter;
use Throwable;

use function array_pop;
use function explode;
use function parse_url;
use function sprintf;

use const PHP_URL_PATH;

class TwitterReplyListener
{
    /** @var SlackClientInterface */
    private $slack;

    /** @var Twitter */
    private $twitter;

    public function __construct(Twitter $twitter, SlackClientInterface $slack)
    {
        $this->twitter = $twitter;
        $this->slack   = $slack;
    }

    public function __invoke(TwitterReply $twitterReply): void
    {
        $replyUrl = $twitterReply->replyUrl();
        try {
            $this->twitter->statusesUpdate(
                $twitterReply->message(),
                $this->getReplyId($replyUrl)
            );
        } catch (Throwable $e) {
            $this->notifySlack(sprintf(
                '*ERROR* Failed to send Twitter reply to %s',
                $replyUrl
            ), $twitterReply->responseUrl());
            return;
        }

        $this->notifySlack(sprintf(
            'Twitter reply sent to %s',
            $replyUrl
        ), $twitterReply->responseUrl());
    }

    private function getReplyId(string $url): string
    {
        $path     = parse_url($url, PHP_URL_PATH);
        $segments = explode('/', $path);
        return array_pop($segments);
    }

    private function notifySlack(string $text, string $responseUrl): void
    {
        $message = new SlashResponseMessage();
        $message->setText($text);
        $this->slack->sendWebhookMessage($responseUrl, $message);
    }
}
