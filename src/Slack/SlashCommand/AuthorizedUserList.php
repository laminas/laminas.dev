<?php

declare(strict_types=1);

namespace App\Slack\SlashCommand;

use App\Slack\SlackClient;
use DomainException;
use Psr\Http\Message\RequestFactoryInterface;

class AuthorizedUserList implements AuthorizedUserListInterface
{
    /** string[] */
    private $allowed;

    /** @var RequestFactoryInterface */
    private $requestFactory;

    /** @var SlackClient */
    private $slack;

    public function __construct(SlackClient $slack, RequestFactoryInterface $requestFactory)
    {
        $this->slack          = $slack;
        $this->requestFactory = $requestFactory;
        $this->build();
    }

    public function isAuthorized(string $userId): bool
    {
        return in_array($userId, $this->allowed, true);
    }

    /**
     * @throws DomainException if unable to fetch channel list
     * @throws DomainException if unable to match #technical-steering-committee
     *     channel in channel list
     * @throws DomainException if unable to fetch list of channel members
     */
    public function build(): void
    {
        $baseUri = 'https://slack.com/api';
        $listUri = sprintf(
            '%s/conversations.list?%s',
            $baseUri,
            http_build_query([
                'exclude_archived' => 'true',
                'types'            => 'private_channel,public_channel',
            ])
        );

        $response = $this->slack->send(
            $this->requestFactory->createRequest('GET', $listUri)
                ->withHeader('Accept', 'application/json')
        );

        if (! $response->isOk()) {
            throw new DomainException(sprintf(
                'Unable to fetch list of channels: %s',
                $response->getError()
            ));
        }

        $channels   = $response->getPayload()['channels'];
        $tscChannel = null;
        foreach ($channels as $channel) {
            if ($channel['name'] === 'technical-steering-committee') {
                $tscChannel = $channel['id'];
                break;
            }
        }

        if ($tscChannel === null) {
            throw new DomainException(sprintf(
                'Did not find #technical-steering-committee channel in list: %s',
                implode(', ', array_column($channels, 'name'))
            ));
        }

        $membersUri = sprintf(
            '%s/conversations.members?%s',
            $baseUri,
            http_build_query([
                'channel' => $tscChannel,
            ])
        );

        $response = $slack->send(
            $requestFactory->createRequest('GET', $listUri)
                ->withHeader('Accept', 'application/json')
        );

        if (! $response->isOk()) {
            throw new DomainException(sprintf(
                'Unable to fetch list of channel members: %s',
                $response->getError()
            ));
        }

        $this->allowed = $response->getPayload()['members'];
    }
}
