<?php

declare(strict_types=1);

namespace App\Slack\Domain;

use InvalidArgumentException;

class Message implements MessageInterface
{
    /** @var BlockInterface[] */
    private $blocks = [];

    /** @var bool */
    private $renderTextAsMarkdown = true;

    /** @var null|string */
    private $text;

    public function addBlock(BlockInterface $block): void
    {
        $this->blocks[] = $block;
    }

    /** @return BlockInterface[] */
    public function getBlocks(): array
    {
        return $this->blocks;
    }

    public function disableTextMarkdown(): void
    {
        $this->renderTextAsMarkdown = false;
    }

    public function enableTextMarkdown(): void
    {
        $this->renderTextAsMarkdown = true;
    }

    public function setText(string $text): void
    {
        $this->text = $text;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function validate(): void
    {
        if (null === $this->text && empty($this->blocks)) {
            throw new InvalidArgumentException(
                'Text must be non-empty and/or one or more blocks must be present in message.'
            );
        }

        array_walk($this->blocks, function (BlockInterface $block) {
            $block->validate();
        });
    }

    public function toArray(): array
    {
        $payload = [];
        if (! empty($this->text)) {
            $payload['text'] = $this->text;
            if (! $this->renderTextAsMarkdown) {
                $payload['mrkdwn'] = false;
            }
        }

        if (! empty($this->blocks)) {
            $payload['blocks'] = array_map(function (BlockInterface $block) {
                return $block->toArray();
            }, $this->blocks);
        }

        return $payload;
    }
}
