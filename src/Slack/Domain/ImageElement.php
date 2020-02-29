<?php

declare(strict_types=1);

namespace App\Slack\Domain;

use Assert\Assert;

class ImageElement implements ElementInterface
{
    /** @var string */
    private $altText;

    /** @var string */
    private $url;

    public static function fromArray(array $data): self
    {
        return new self(
            $data['url'] ?? '',
            $data['alt_text'] ?? ''
        );
    }

    public function __construct(string $url, string $altText = '')
    {
        $this->url     = $url;
        $this->altText = $altText;
    }

    public function validate(): void
    {
        Assert::that($this->url)->url();
        Assert::that($this->altText)->notEmpty();
    }

    public function toArray(): array
    {
        return [
            'type'     => 'image',
            'url'      => $this->url,
            'alt_text' => $this->altText,
        ];
    }
}
