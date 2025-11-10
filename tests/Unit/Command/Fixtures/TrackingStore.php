<?php

declare(strict_types=1);

namespace Andez\SelectiveFixturesLoaderBundle\Tests\Unit\Command\Fixtures;

final class TrackingStore
{
    /** @var list<class-string> */
    public static array $loaded = [];

    public static function reset(): void
    {
        self::$loaded = [];
    }
}

