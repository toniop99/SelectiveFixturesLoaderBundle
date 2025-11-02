<?php

namespace Andez\SelectiveFixturesLoaderBundle;

use Doctrine\Common\DataFixtures\FixtureInterface;
use ReflectionException;

final class FixtureCollector
{
    /**
     * @param object[] $fixtures
     */
    public function __construct(private readonly iterable $fixtures)
    {
    }

    /**
     * @param string $className
     * @return FixtureInterface|null
     * @throws ReflectionException
     */
    public function findFixtureByClassName(string $className): ?FixtureInterface
    {
        foreach ($this->fixtures as $fixture) {
            $reflectionClass = new \ReflectionClass($fixture);
            $fixtureShortName = $reflectionClass->getShortName();
            $fixtureClassName = get_class($fixture);

            if ($fixtureClassName === $className || $fixtureShortName === $className) {
                return $fixture;
            }
        }

        return null;
    }

}
