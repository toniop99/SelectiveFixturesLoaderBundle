<?php

declare(strict_types=1);

namespace Andez\SelectiveFixturesLoaderBundle\Tests\Unit\Command\Fixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class TrackingProfileFixture implements FixtureInterface, DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        TrackingStore::$loaded[] = self::class;
    }

    /** @return array<class-string<FixtureInterface>> */
    public function getDependencies(): array
    {
        return [TrackingUserFixture::class];
    }
}

