<?php

declare(strict_types=1);

namespace App\Slack\Domain;

use Assert\Assert;

/**
 * @see https://api.slack.com/docs/message-attachments
 */
class Attachment
{
    /** @var array array<string, mixed> */
    private $payload;

    public function __construct(array $payload)
    {
        if (! isset($payload['color'])) {
            $payload['color'] = AttachmentColor::DEFAULT;
        }

        $this->validatePayload($payload);

        $markdownFields = $this->identifyMarkdownFields($payload);
        if (! empty($markdownFields)) {
            $payload['mrkdwn_in'] = $markdownFields;
        }

        $this->payload = $payload;
    }

    public function toArray() : array
    {
        return $this->payload;
    }

    private function validatePayload($payload): void
    {
        Assert::that($payload)->keyIsset('fallback')->string()->notEmpty();
        Assert::that($payload['fallback'])->string()->notEmpty();

        Assert::that($payload['color'])->satisfy([AttachmentColor::class, 'validate']);

        Assert::that($payload)->keyIsset('title');
        Assert::that($payload['title'])->string()->notEmpty();

        Assert::that($payload)->keyIsset('title_link');
        Assert::that($payload['title_link'])->url();

        Assert::that($payload)->keyIsset('text');
        Assert::that($payload['text'])->string();

        Assert::that($payload)->keyIsset('footer');
        Assert::that($payload['footer'])->string();

        Assert::that($payload)->keyIsset('ts');
        Assert::that($payload['ts'])->integer();
    }

    private function identifyMarkdownFields(array $payload): array
    {
        $fields = [];
        if (isset($payload['text'])) {
            $fields[] = 'text';
        }
        if (isset($payload['pretext'])) {
            $fields[] = 'pretext';
        }
        return $fields;
    }
}
