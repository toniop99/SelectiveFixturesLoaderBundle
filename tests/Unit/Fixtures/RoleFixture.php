<?php

declare(strict_types=1);

namespace Andez\SelectiveFixturesLoaderBundle\Tests\Unit\Fixtures;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class RoleFixture implements FixtureInterface
{
    public function load(ObjectManager $manager): void
    {
    }
}
