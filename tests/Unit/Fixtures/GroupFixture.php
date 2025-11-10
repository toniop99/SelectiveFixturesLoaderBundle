<?php

declare(strict_types=1);

namespace Andez\SelectiveFixturesLoaderBundle\Tests\Unit\Fixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class GroupFixture implements FixtureInterface, DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
    }

    /** @return array<class-string<FixtureInterface>> */
    public function getDependencies(): array
    {
        return [RoleFixture::class];
    }
}
