<?php

declare(strict_types=1);

namespace App\Slack\SlashCommand;

use App\Slack\Event\Tweet;
use Assert\Assert;
use Assert\AssertionFailedException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;

use function preg_match;
use function preg_replace;
use function strlen;
use function trim;

class TweetCommand implements SlashCommandInterface
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
        return 'tweet';
    }

    public function usage(): string
    {
        return '[media:url] message';
    }

    public function help(): string
    {
        return 'Tweet the [message] via the getlaminas account.'
            . ' You can optionally provide media to embed using a "media:url" pair'
            . ' (no brackets required).';
    }

    public function validate(
        SlashCommandRequest $request,
        AuthorizedUserListInterface $authorizedUsers
    ): ?ResponseInterface {
        if (! $authorizedUsers->isAuthorized($request->userId())) {
            return $this->responseFactory->createUnauthorizedResponse();
        }

        $text    = $request->text();
        $message = $this->normalizeMessage($text);
        $length  = strlen($message);
        if ($length > 280 || $length === 0) {
            return $this->responseFactory->createResponse(
                'Message MUST be at least 1 and no more than 280 characters.'
            );
        }

        $media = $this->discoverMedia($text);
        if (null === $media) {
            return null;
        }

        try {
            Assert::that($media)->url();
        } catch (AssertionFailedException $e) {
            return $this->responseFactory->createResponse(
                'Media value MUST be a valid URL.'
            );
        }

        return null;
    }

    public function dispatch(SlashCommandRequest $request): ResponseInterface
    {
        $text = $request->text();
        $this->dispatcher->dispatch(new Tweet(
            $this->normalizeMessage($text),
            $this->discoverMedia($text),
            $request->responseUrl()
        ));
        return $this->responseFactory->createResponse('Tweet queued');
    }

    private function normalizeMessage(string $text): string
    {
        $text = preg_replace('#media:\S+#', '', $text);
        return trim($text);
    }

    private function discoverMedia(string $text): ?string
    {
        $matches = [];
        if (! preg_match('#media:(?P<url>\S+)#', $text, $matches)) {
            return null;
        }
        return $matches['url'];
    }
}
