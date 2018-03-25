<?php

declare(strict_types=1);

namespace App\GitHub\Handler;

use App\GitHub\Message\GitHubPush;
use App\Slack\Domain\Attachment;
use App\Slack\Domain\AttachmentColor;
use App\Slack\Method\ChatPostMessage;
use App\Slack\SlackClientInterface;
use Xtreamwayz\Expressive\Messenger\Exception\RejectMessageException;
use function sprintf;

class GitHubPushHandler
{
    /** @var SlackClientInterface */
    private $slackClient;

    public function __construct(SlackClientInterface $slackClient)
    {
        $this->slackClient = $slackClient;
    }

    public function __invoke(GitHubPush $message) : void
    {
        // Build attachment
        $summary    = $message->getSummary();
        $attachment = (new Attachment($summary, AttachmentColor::default()))
            ->addText(sprintf('*%s*', $summary))
            ->withFooter($message->getFooter());

        foreach ($message->getCommits() as $commit) {
            $attachment->addText($commit);
        }

        // Build message
        $apiRequest = (new ChatPostMessage($this->slackClient->getDefaultChannel()))
            ->addAttachment($attachment);

        $response = $this->slackClient->sendApiRequest($apiRequest);
        if (! $response->isOk()) {
            throw new RejectMessageException($response->getError(), $response->getStatusCode());
        }
    }
}
