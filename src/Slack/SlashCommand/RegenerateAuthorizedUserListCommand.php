<?php

declare(strict_types=1);

namespace App\Slack\SlashCommand;

use App\Slack\Event\RegenerateAuthorizedUserList;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;

use function sprintf;

class RegenerateAuthorizedUserListCommand implements SlashCommandInterface
{
    /** @var string */
    private $aclChannel;

    /** @var EventDispatcherInterface */
    private $dispatcher;

    /** @var SlashCommandResponseFactory */
    private $responseFactory;

    public function __construct(
        SlashCommandResponseFactory $responseFactory,
        EventDispatcherInterface $dispatcher,
        string $aclChannel = AuthorizedUserList::DEFAULT_ACL_CHANNEL
    ) {
        $this->responseFactory = $responseFactory;
        $this->dispatcher      = $dispatcher;
        $this->aclChannel      = $aclChannel;
    }

    public function command(): string
    {
        return 'regenerate-tsc-list';
    }

    public function usage(): string
    {
        return '';
    }

    public function help(): string
    {
        return sprintf(
            'Issue this command to regenerate the authorized users list from'
            . ' the set of current members of the #%s channel.',
            $this->aclChannel
        );
    }

    public function validate(
        SlashCommandRequest $request,
        AuthorizedUserListInterface $authorizedUsers
    ): ?ResponseInterface {
        if (! $authorizedUsers->isAuthorized($request->userId())) {
            return $this->responseFactory->createUnauthorizedResponse();
        }
        return null;
    }

    public function dispatch(SlashCommandRequest $request): ?ResponseInterface
    {
        $this->dispatcher->dispatch(new RegenerateAuthorizedUserList($request->responseUrl()));
        return null;
    }
}
