<?php

declare(strict_types=1);

namespace Andez\SelectiveFixturesLoaderBundle\Tests\Unit\Command\Fixtures;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class TrackingProductFixture implements FixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        TrackingStore::$loaded[] = self::class;
    }
}

