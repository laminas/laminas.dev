<?php

declare(strict_types=1);

namespace App\Slack\Listener;

use App\HttpClientInterface;
use App\Slack\Domain\SlashResponseMessage;
use App\Slack\Event\Tweet;
use App\Slack\SlackClientInterface;
use Laminas\Twitter\Image;
use Laminas\Twitter\Twitter;
use Throwable;

use function basename;
use function implode;
use function in_array;
use function sprintf;
use function strtolower;

class TweetListener
{
    private const ALLOWED_IMAGE_TYPES = [
        'image/gif',
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    /**
     * @internal
     *
     * @var callable
     */
    public $filePopulator = 'file_put_contents';

    /**
     * @internal
     *
     * @var callable
     */
    public $imageFactory;

    /** @var HttpClientInterface */
    private $http;

    /** @var SlackClientInterface */
    private $slack;

    /** @var Twitter */
    private $twitter;

    public function __construct(
        Twitter $twitter,
        SlackClientInterface $slack,
        HttpClientInterface $http
    ) {
        $this->twitter      = $twitter;
        $this->slack        = $slack;
        $this->http         = $http;
        $this->imageFactory = function (string $filename, string $mediaType) {
            return new Image($filename, $mediaType);
        };
    }

    public function __invoke(Tweet $tweet): void
    {
        if ($tweet->media()) {
            $this->sendTweetWithMedia($tweet);
            return;
        }

        try {
            $this->twitter->statusesUpdate($tweet->message());
        } catch (Throwable $e) {
            $this->reportError(
                sprintf('*ERROR* sending tweet: %s', $e->getMessage()),
                $tweet
            );
            return;
        }

        $this->reportSuccess($tweet);
    }

    public function sendTweetWithMedia(Tweet $tweet): void
    {
        $mediaId = $this->uploadMedia($tweet);
        if (null === $mediaId) {
            return;
        }

        try {
            $this->twitter->statusesUpdate(
                $tweet->message(),
                null,
                ['media_ids' => [$mediaId]]
            );
        } catch (Throwable $e) {
            $this->reportError(
                sprintf('*ERROR* sending tweet: %s', $e->getMessage()),
                $tweet
            );
            return;
        }

        $this->reportSuccess($tweet);
    }

    private function reportSuccess(Tweet $tweet): void
    {
        $message = new SlashResponseMessage();
        $message->setText(sprintf(
            'Tweet sent with message: %s',
            $tweet->message()
        ));
        $this->slack->sendWebhookMessage($tweet->responseUrl(), $message);
    }

    private function reportError(string $error, Tweet $tweet): void
    {
        $message = new SlashResponseMessage();
        $message->setText($error);
        $this->slack->sendWebhookMessage($tweet->responseUrl(), $message);
    }

    private function uploadMedia(Tweet $tweet): ?string
    {
        $url  = $tweet->media();
        $type = $this->determineImageType($tweet);
        if (null === $type) {
            return null;
        }

        $filename = $this->fetchImage($tweet);
        if (null === $filename) {
            return null;
        }

        $image = ($this->imageFactory)($filename, $type);
        try {
            $response = $image->upload($this->twitter->getHttpClient());
        } catch (Throwable $e) {
            $this->reportError(sprintf(
                '*ERROR* Unable to upload media from %s: %s',
                $url,
                $e->getMessage()
            ), $tweet);
            return null;
        }

        return $response->media_id;
    }

    private function determineImageType(Tweet $tweet): ?string
    {
        $request     = $this->http->createRequest('HEAD', $tweet->media());
        $response    = $this->http->send($request);
        $contentType = $response->getHeaderLine('Content-Type');
        if (! in_array(strtolower($contentType), self::ALLOWED_IMAGE_TYPES, true)) {
            $this->reportError(sprintf(
                '*ERROR* Media is of content type "%s"; must be one of %s',
                $contentType,
                implode(', ', self::ALLOWED_IMAGE_TYPES)
            ), $tweet);
            return null;
        }
        return $contentType;
    }

    private function fetchImage(Tweet $tweet): ?string
    {
        $url      = $tweet->media();
        $request  = $this->http->createRequest('GET', $tweet->media());
        $response = $this->http->send($request);

        if (200 !== $response->getStatusCode()) {
            $this->reportError(sprintf('*ERROR* Unable to fetch media from %s', $url), $tweet);
            return null;
        }

        $filename = sprintf('data/cache/%s', basename($url));
        ($this->filePopulator)($filename, (string) $response->getBody());

        return $filename;
    }
}
