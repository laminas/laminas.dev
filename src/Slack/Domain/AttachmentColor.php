<?php

declare(strict_types=1);

namespace App\Slack\Domain;

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

    private const VALID_COLORS = [
        self::SUCCESS,
        self::WARNING,
        self::DANGER,
    ];

    public static function validate(string $hexColor): bool
    {
        $color = strtolower($hexColor);
        if (! in_array($color, self::VALID_COLORS, true)
            && preg_match('/#([a-f0-9]{6})\b/i', $color) !== 1
        ) {
            return false;
        }
        return true;
    }
}
