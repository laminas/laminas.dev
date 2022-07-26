<?php

declare(strict_types=1);

namespace App\Slack\SlashCommand;

use App\Slack\SlackClientInterface;
use DomainException;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Log\LoggerInterface;

use function array_column;
use function http_build_query;
use function implode;
use function in_array;
use function sprintf;
use function var_export;

class AuthorizedUserList implements AuthorizedUserListInterface
{
    public const DEFAULT_ACL_CHANNEL = 'technical-steering-committee';

    /** @var string */
    private $aclChannel;

    /** @var string[] */
    private $allowed = [];

    /** @var null|LoggerInterface */
    private $logger;

    /** @var RequestFactoryInterface */
    private $requestFactory;

    /** @var SlackClientInterface */
    private $slack;

    public function __construct(
        SlackClientInterface $slack,
        RequestFactoryInterface $requestFactory,
        string $aclChannel = self::DEFAULT_ACL_CHANNEL,
        ?LoggerInterface $logger = null
    ) {
        $this->slack          = $slack;
        $this->requestFactory = $requestFactory;
        $this->aclChannel     = $aclChannel;
        $this->logger         = $logger;
    }

    public function isAuthorized(string $userId): bool
    {
        return in_array($userId, $this->allowed, true);
    }

    /**
     * @throws DomainException If unable to fetch channel list.
     * @throws DomainException If unable to match #technical-steering-committee
     *     channel in channel list.
     * @throws DomainException If unable to fetch list of channel members.
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
                ->withHeader('Accept', 'application/json; charset=utf-8')
        );

        if (! $response->isOk()) {
            $message = sprintf('Unable to fetch list of channels: %s', $response->getError());
            $this->log($message);
            throw new DomainException($message);
        }

        $channels   = $response->getPayload()['channels'];
        $aclChannel = null;
        foreach ($channels as $channel) {
            if ($channel['name'] === $this->aclChannel) {
                $aclChannel = $channel['id'];
                break;
            }
        }

        if ($aclChannel === null) {
            $message = sprintf(
                'Did not find #%s channel in list: %s',
                $this->aclChannel,
                implode(', ', array_column($channels, 'name'))
            );
            $this->log($message);
            throw new DomainException($message);
        }

        $membersUri = sprintf(
            '%s/conversations.members?%s',
            $baseUri,
            http_build_query([
                'channel' => $aclChannel,
            ])
        );

        $response = $this->slack->send(
            $this->requestFactory->createRequest('GET', $membersUri)
                ->withHeader('Accept', 'application/json; charset=utf-8')
        );

        if (! $response->isOk()) {
            $message = sprintf('Unable to fetch list of channel members: %s', $response->getError());
            $this->log($message);
            throw new DomainException($message);
        }

        $this->log(sprintf(
            'Received following membership payload: %s',
            var_export($response->getPayload(), true)
        ));

        $this->allowed = $response->getPayload()['members'];
    }

    private function log(string $message): void
    {
        if (! $this->logger) {
            return;
        }

        $this->logger->debug($message);
    }
}
