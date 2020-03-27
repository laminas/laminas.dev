<?php

declare(strict_types=1);

namespace App\Slack\SlashCommand;

use App\Slack\Event\TwitterReply;
use Assert\Assert;
use Assert\AssertionFailedException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;

use function explode;
use function preg_match;
use function strlen;
use function trim;

class TwitterReplyCommand implements SlashCommandInterface
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
        return 'reply-to-tweet';
    }

    public function usage(): string
    {
        return '{url} {message}';
    }

    public function help(): string
    {
        return 'Reply to the tweet represented by {url} via the getlaminas account.';
    }

    public function validate(
        SlashCommandRequest $request,
        AuthorizedUserListInterface $authorizedUsers
    ): ?ResponseInterface {
        if (! $authorizedUsers->isAuthorized($request->userId())) {
            return $this->responseFactory->createUnauthorizedResponse();
        }

        $text    = trim($request->text());
        $url     = $this->parseReplyUrlFromText($text);
        $message = $this->parseMessageFromText($text);

        try {
            Assert::that($url)->url()->regex('#^https://twitter.com/[^/]+/status/[^/]+$#');
        } catch (AssertionFailedException $e) {
            return $this->responseFactory->createResponse(
                'Reply URL MUST be a valid URL pointing to a tweet.'
            );
        }

        $length = strlen($message);
        if ($length > 280 || $length === 0) {
            return $this->responseFactory->createResponse(
                'Message MUST be at least 1 and no more than 280 characters.'
            );
        }

        return null;
    }

    public function dispatch(SlashCommandRequest $request): ?ResponseInterface
    {
        $text = trim($request->text());
        $this->dispatcher->dispatch(new TwitterReply(
            $this->parseReplyUrlFromText($text),
            $this->parseMessageFromText($text),
            $request->responseUrl()
        ));
        return null;
    }

    private function parseMessageFromText(string $text): string
    {
        if (! preg_match('/\s/', $text)) {
            return '';
        }

        [$url, $message] = explode(' ', $text, 2);
        return $message;
    }

    private function parseReplyUrlFromText(string $text): ?string
    {
        if (! preg_match('/\s/', $text)) {
            return $text;
        }

        [$url, $message] = explode(' ', $text, 2);
        return $url;
    }
}
