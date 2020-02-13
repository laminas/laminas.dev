<?php

declare(strict_types=1);

namespace App\GitHub\Listener;

use App\GitHub\Event\GitHubPullRequest;
use App\Slack\Domain\Attachment;
use App\Slack\Domain\AttachmentColor;
use App\Slack\Method\ChatPostMessage;
use App\Slack\SlackClientInterface;
use Xtreamwayz\Mezzio\Messenger\Exception\RejectMessageException;

class GitHubPullRequestListener
{
    /** @var SlackClientInterface */
    private $slackClient;

    public function __construct(SlackClientInterface $slackClient)
    {
        $this->slackClient = $slackClient;
    }

    public function __invoke(GitHubPullRequest $message) : void
    {
        // Build message
        $apiRequest = new ChatPostMessage($this->slackClient->getDefaultChannel());

        // Build attachments
        $summary    = $message->getSummary();
        $attachment = (new Attachment($summary, AttachmentColor::default()))
            ->withPretext($summary)
            ->addText($message->getCommitMessage());
        $apiRequest->addAttachment($attachment);

        $attachment = (new Attachment($message->getTitle(), AttachmentColor::default()))
            ->withTitle($message->getTitle())
            ->withTitleLink($message->getTitleLink())
            ->withFooter($message->getFooter());
        $apiRequest->addAttachment($attachment);

        $response = $this->slackClient->sendApiRequest($apiRequest);
        if (! $response->isOk()) {
            throw new RejectMessageException($response->getError(), $response->getStatusCode());
        }
    }
}
