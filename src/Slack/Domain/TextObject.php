<?php

declare(strict_types=1);

namespace App\Slack\Domain;

use Assert\InvalidArgumentException;

use function array_key_exists;
use function in_array;
use function sprintf;

class TextObject implements ElementInterface
{
    public const TYPE_MARKDOWN = 'mrkdwn';

    public const TYPE_PLAIN_TEXT = 'plain_text';

    private const ALLOWED_TYPES = [
        self::TYPE_MARKDOWN,
        self::TYPE_PLAIN_TEXT,
    ];

    /** @var bool */
    private $escapeEmoji;

    /** @var string */
    private $text;

    /** @var string */
    private $type;

    /** @var bool */
    private $renderReferencesVerbatim;

    public static function fromArray(array $data): self
    {
        return new self(
            $data['text'] ?? '',
            $data['type'] ?? self::TYPE_MARKDOWN,
            array_key_exists('emoji', $data) ? ! $data['emoji'] : false,
            array_key_exists('verbatim', $data) ? $data['verbatim'] : false
        );
    }

    public function __construct(
        string $text,
        string $type = self::TYPE_MARKDOWN,
        bool $escapeEmoji = false,
        bool $renderReferencesVerbatim = false
    ) {
        $this->text                     = $text;
        $this->type                     = $type;
        $this->escapeEmoji              = $escapeEmoji;
        $this->renderReferencesVerbatim = $renderReferencesVerbatim;
    }

    /** {@inheritDocs} */
    public function validate(): void
    {
        if (! in_array($this->type, self::ALLOWED_TYPES, true)) {
            throw new InvalidArgumentException(sprintf(
                'Text objects must have a type of either "%s" or "%s"; received "%s"',
                self::TYPE_PLAIN_TEXT,
                self::TYPE_MARKDOWN,
                $this->type
            ), 0, 'type', []);
        }
    }

    public function toArray(): array
    {
        $representation = [
            'type' => $this->type,
            'text' => $this->text,
        ];

        if ($this->type === self::TYPE_MARKDOWN && $this->renderReferencesVerbatim) {
            $representation['verbatim'] = true;
        }

        if ($this->type === self::TYPE_PLAIN_TEXT && $this->escapeEmoji) {
            $representation['emoji'] = false;
        }

        return $representation;
    }
}
