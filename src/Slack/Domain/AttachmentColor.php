<?php

declare(strict_types=1);

namespace App\Slack\Domain;

use DomainException;
use function in_array;
use function preg_match;
use function strtolower;

class AttachmentColor
{
    public const DEFAULT = '#9e9e9e';

    public const INFO = '#03a9f4';

    public const SUCCESS = 'good';

    public const WARNING = 'warning';

    public const DANGER = 'danger';

    /** @var string */
    private $color;

    public function __construct(string $hexColor)
    {
        $color = strtolower($hexColor);
        if (! in_array($color, ['good', 'warning', 'danger'], true)
            && preg_match('/#([a-f0-9]{6})\b/i', $color) !== 1
        ) {
            throw new DomainException('Invalid hex color string');
        }

        $this->color = $color;
    }

    public static function default() : AttachmentColor
    {
        return new self(self::DEFAULT);
    }

    public static function info() : AttachmentColor
    {
        return new self(self::INFO);
    }

    public static function success() : AttachmentColor
    {
        return new self(self::SUCCESS);
    }

    public static function warning() : AttachmentColor
    {
        return new self(self::WARNING);
    }

    public static function danger() : AttachmentColor
    {
        return new self(self::DANGER);
    }

    public function __toString() : string
    {
        return $this->color;
    }
}
