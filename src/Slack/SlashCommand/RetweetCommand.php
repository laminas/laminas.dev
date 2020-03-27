<?php

declare(strict_types=1);

namespace App\Slack\SlashCommand;

use App\Slack\Event\Retweet;
use Assert\Assert;
use Assert\AssertionFailedException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;

use function trim;

class RetweetCommand implements SlashCommandInterface
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
        return 'retweet';
    }

    public function usage(): string
    {
        return '[url]';
    }

    public function help(): string
    {
        return 'Retweet an existing tweet, referenced by the url, via the getlaminas account.';
    }

    public function validate(
        SlashCommandRequest $request,
        AuthorizedUserListInterface $authorizedUsers
    ): ?ResponseInterface {
        if (! $authorizedUsers->isAuthorized($request->userId())) {
            return $this->responseFactory->createUnauthorizedResponse();
        }

        $url = trim($request->text());

        try {
            Assert::that($url)->url()->regex('#^https://twitter.com/[^/]+/status/[^/]+$#');
        } catch (AssertionFailedException $e) {
            return $this->responseFactory->createResponse(
                'Retweet URL MUST be a valid URL pointing to a tweet.'
            );
        }

        return null;
    }

    public function dispatch(SlashCommandRequest $request): ?ResponseInterface
    {
        $text = $request->text();
        $this->dispatcher->dispatch(new Retweet(
            trim($request->text()),
            $request->responseUrl()
        ));
        return null;
    }
}
