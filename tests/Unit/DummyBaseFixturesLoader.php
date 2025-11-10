<?php

declare(strict_types=1);

namespace Andez\SelectiveFixturesLoaderBundle\Tests\Unit;

use Andez\SelectiveFixturesLoaderBundle\BaseFixturesLoaderInterface;

final readonly class DummyBaseFixturesLoader implements BaseFixturesLoaderInterface
{
    /** @param class-string[] $fixtures */
    public function __construct(private array $fixtures)
    {
    }

    /** @return class-string[] */
    public function getBaseFixtures(): array
    {
        return $this->fixtures;
    }
}
