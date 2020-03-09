<?php

declare(strict_types=1);

namespace App\Slack\Domain;

use Assert\Assert;

use function array_merge;

class SlashResponseMessage extends Message
{
    public const TYPE_EPHEMERAL  = 'ephemeral';
    public const TYPE_IN_CHANNEL = 'in_channel';

    private const ALLOWED_TYPES = [
        self::TYPE_EPHEMERAL,
        self::TYPE_IN_CHANNEL,
    ];

    /** @var string */
    private $responseType = self::TYPE_EPHEMERAL;

    public function setResponseType(string $type): void
    {
        $this->responseType = $type;
    }

    public function getResponseType(): string
    {
        return $this->responseType;
    }

    public function validate(): void
    {
        Assert::that($this->responseType)->inArray(self::ALLOWED_TYPES);
        parent::validate();
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'response_type' => $this->responseType,
        ]);
    }
}
