<?php

declare(strict_types=1);

namespace App\Slack\Listener;

use App\Slack\Domain\SlashResponseMessage;
use App\Slack\Event\Retweet;
use App\Slack\SlackClientInterface;
use Laminas\Twitter\Twitter;
use Throwable;

use function sprintf;
use function strrpos;
use function substr;

class RetweetListener
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

    public function __invoke(Retweet $retweet): void
    {
        $original = $retweet->original();
        $id       = substr($original, strrpos($original, '/') + 1);

        try {
            $this->twitter->post(sprintf('statuses/retweet/%s', $id));
        } catch (Throwable $e) {
            $this->report(sprintf(
                '*ERROR* Unable to retweet %s: %s',
                $original,
                $e->getMessage()
            ), $retweet->responseUrl());
            return;
        }

        $this->report('Retweet sent', $retweet->responseUrl());
    }

    private function report(string $message, string $responseUrl): void
    {
        $response = new SlashResponseMessage();
        $response->setText($message);
        $this->slack->sendWebhookMessage($responseUrl, $response);
    }
}
