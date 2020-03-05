<?php

declare(strict_types=1);

namespace AppTest\Slack\Domain;

use App\Slack\Domain\ImageElement;
use Assert\AssertionFailedException;
use PHPUnit\Framework\TestCase;

class ImageElementTest extends TestCase
{
    public function testInvalidatesImageIfUrlIsInvalid(): void
    {
        $image = new ImageElement('this is not a valid url', 'alt text');

        $this->expectException(AssertionFailedException::class);
        $image->validate();
    }

    public function testInvalidatesImageIfAltTextIsEmpty(): void
    {
        $image = new ImageElement('https://getlaminas.org/images/logo/laminas-foundation-rgb.svg', '');

        $this->expectException(AssertionFailedException::class);
        $image->validate();
    }

    public function testRendersAsExpectedBySlack(): void
    {
        $image = new ImageElement(
            'https://getlaminas.org/images/logo/laminas-foundation-rgb.svg',
            'Laminas icon'
        );

        $this->assertSame([
            'type'      => 'image',
            'image_url' => 'https://getlaminas.org/images/logo/laminas-foundation-rgb.svg',
            'alt_text'  => 'Laminas icon',
        ], $image->toArray());
    }
}
