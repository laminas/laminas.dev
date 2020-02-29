<?php

declare(strict_types=1);

namespace App\Slack\Listener;

use App\Slack\Domain\SlashResponseMessage;
use App\Slack\Event\RegenerateAuthorizedUserList;
use App\Slack\SlackClientInterface;
use App\Slack\SlashCommand\AuthorizedUserListInterface;
use DomainException;

class RegenerateAuthorizedUserListListener
{
    /** @var AuthorizedUserListInterface */
    private $authorizedUserList;

    /** @var SlackClientInterface */
    private $slack;

    public function __construct(
        AuthorizedUserListInterface $authorizedUserList,
        SlackClientInterface $slack
    ) {
        $this->authorizedUserList = $authorizedUserList;
        $this->slack              = $slack;
    }

    public function __invoke(RegenerateAuthorizedUserList $request): void
    {
        try {
            $this->authorizedUserList->build();
        } catch (DomainException $e) {
            $this->reportError($e, $request);
            return;
        }

        $message = new SlashResponseMessage();
        $message->setText('*Queueing request to rebuild authorized user list*');

        $this->slack->sendWebhookMessage($request->responseUrl(), $message);
    }

    private function reportError(DomainException $e, RegenerateAuthorizedUserList $request): void
    {
        $message = new SlashResponseMessage();
        $message->setText(sprintf(
            '*Error rebuilding authorized user list with message "%s";'
            . ' ask Matthew to check the error logs for details',
            $e->getMessage()
        ));
        $this->slack->sendWebhookMessage($request->responseUrl(), $message);
    }
}
