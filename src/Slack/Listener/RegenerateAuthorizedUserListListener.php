<?php

declare(strict_types=1);

namespace App\Slack\Listener;

use App\Slack\Event\RegenerateAuthorizedUserList;
use App\Slack\SlackClientInterface;
use App\Slack\SlashCommand\AuthorizedUserList;
use DomainException;

class RegenerateAuthorizedUserListListener
{
    /** @var AuthorizedUserList */
    private $authorizedUserList;

    /** @var SlackClientInterface */
    private $slack;

    public function __construct(
        AuthorizedUserList $authorizedUserList,
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

        $this->slack->sendWebhookMessage($request->responseUrl(), [
            'response_type' => 'ephemeral',
            'mrkdwn'        => true,
            'text'          => '*Queueing request to rebuild authorized user list*',
        ]);
    }

    private function reportError(DomainException $e, RegenerateAuthorizedUserList $request): void
    {
        $this->slack->sendWebhookMessage($request->responseUrl(), [
            'response_type' => 'ephemeral',
            'mrkdwn'        => true,
            'text'          => sprintf(
                '*Error rebuilding authorized user list with message "%s";'
                . ' ask Matthew to check the error logs for details',
                $e->getMessage()
            ),
        ]);
    }
}
