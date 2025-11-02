<?php

namespace Andez\SelectiveFixturesLoaderBundle;

/**
 * @internal
 */
final readonly class ArrayBaseFixturesLoader implements BaseFixturesLoaderInterface
{
    /**
     * @param class-string[] $baseFixtures
     */
    public function __construct(
        private array $baseFixtures
    )
    {
    }

    /**
     * @return class-string[]
     */
    public function getBaseFixtures(): array
    {
        return $this->baseFixtures;
    }
}
