<?php

declare(strict_types=1);

namespace App\Slack\Domain;

interface MessageInterface extends
    RenderableInterface,
    ValidatableInterface
{
    public function addBlock(BlockInterface $block): void;
    public function disableTextMarkdown(): void;
    public function enableTextMarkdown(): void;
    public function setText(string $text): void;
}
