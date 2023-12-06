<?php

declare(strict_types=1);

namespace AppTest;

use Laminas\Diactoros\StreamFactory;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

class Psr7Helper
{
    public static ?StreamFactoryInterface $streamFactory = null;

    public static function streamFactory(): StreamFactoryInterface
    {
        if (self::$streamFactory === null) {
            self::$streamFactory = new StreamFactory();
        }

        return self::$streamFactory;
    }

    public static function stream(string $content): StreamInterface
    {
        return self::streamFactory()->createStream($content);
    }
}
