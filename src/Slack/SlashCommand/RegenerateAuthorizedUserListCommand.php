<?php

declare(strict_types=1);

namespace App\Slack\SlashCommand;

use App\Slack\Event\RegenerateAuthorizedUserList;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;

class RegenerateAuthorizedUserListCommand implements SlashCommandInterface
{
    /** @var EventDispatcherInterface */
    private $dispatcher;

    /** @var SlashCommandResponseFactory */
    private $responseFactory;

    public function __construct(
        SlashCommandResponseFactory $responseFactory,
        EventDispatcherInterface $dispatcher
    ) {
        $this->responseFactory = $responseFactory;
        $this->dispatcher      = $dispatcher;
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
        return 'Issue this command to regenerate the authorized users list from'
           . ' the set of current members of the #technical-steering-committee channel.';
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

    public function dispatch(SlashCommandRequest $request): ResponseInterface
    {
        $this->dispatcher->dispatch(new RegenerateAuthorizedUserList($request->responseUrl()));

        return $this->responseFactory->createResponse('Triggered rebuild of authorized user list');
    }
}
