<?php

declare(strict_types=1);

namespace App\Slack\Domain;

use Assert\Assert;

trait ImageElementValidationTrait
{
    private function validateImageElement(array $element): void
    {
        Assert::that($element)->keyIsset('image_url');
        Assert::that($element['image_url'])->string();
        Assert::that($element)->keyIsset('alt_text');
        Assert::that($element['alt_text'])->string();
    }
}
