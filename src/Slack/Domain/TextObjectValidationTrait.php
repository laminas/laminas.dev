<?php

declare(strict_types=1);

namespace App\Slack\Domain;

use Assert\Assert;

trait TextObjectValidationTrait
{
    private function validateTextObject(array $textObject): void
    {
        Assert::that($textObject)->keyIsset('text');
        Assert::that($textObject['text'])->string();
        Assert::that($textObject)->keyIsset('type');
        Assert::that($textObject['type'])->string()->inArray(['plain_text', 'mrkdwn']);
    }
}
