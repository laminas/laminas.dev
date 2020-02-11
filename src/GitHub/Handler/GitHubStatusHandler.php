<?php

declare(strict_types=1);

namespace App\GitHub\Handler;

use App\GitHub\Message\GitHubStatus;
use App\Slack\Domain\Attachment;
use App\Slack\Domain\AttachmentColor;
use App\Slack\Method\ChatPostMessage;
use App\Slack\SlackClientInterface;
use Xtreamwayz\Mezzio\Messenger\Exception\RejectMessageException;
use function sprintf;

class GitHubStatusHandler
{
    /** @var SlackClientInterface */
    private $slackClient;

    public function __construct(SlackClientInterface $slackClient)
    {
        $this->slackClient = $slackClient;
    }

    public function __invoke(GitHubStatus $message) : void
    {
        // Attachment color
        switch ($message->getState()) {
            case 'success':
                $color = AttachmentColor::success();
                break;

            case 'failure':
            case 'error':
            default:
                $color = AttachmentColor::danger();
                break;
        }

        // Build attachment
        $summary    = $message->getSummary();
        $attachment = (new Attachment($summary, $color))
            ->addText(sprintf('*%s*', $summary));

        $commit = $message->getCommit();
        if ($commit) {
            $attachment->addText($commit);
        }

        // Build message
        $apiRequest = new ChatPostMessage($this->slackClient->getDefaultChannel());
        $apiRequest->addAttachment($attachment);

        $response = $this->slackClient->sendApiRequest($apiRequest);
        if (! $response->isOk()) {
            throw new RejectMessageException($response->getError(), $response->getStatusCode());
        }
    }
}
