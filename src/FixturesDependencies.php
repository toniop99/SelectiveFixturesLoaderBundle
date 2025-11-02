<?php

namespace Andez\SelectiveFixturesLoaderBundle;

use Doctrine\Bundle\FixturesBundle\Loader\SymfonyFixturesLoader;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

final readonly class FixturesDependencies
{
    public function __construct(
        private FixtureCollector $collector,
        private ?BaseFixturesLoaderInterface $baseFixturesLoader = null,
        private SymfonyFixturesLoader $fixturesLoader,
    )
    {
    }

    /**
     * @return class-string[]
     */
    private function baseFixtures(): array
    {
        if (null === $this->baseFixturesLoader) {
            return [];
        }

        return $this->baseFixturesLoader->getBaseFixtures();
    }

    private function collectDependencies(string ...$fixtureClass): array
    {
        $dependencies = [];

        foreach ($fixtureClass as $class) {
            $dependencies[$class] = true;
            $fixture              = $this->collector->findFixtureByClassName($class);

            if (! $fixture instanceof DependentFixtureInterface) {
                continue;
            }

            $dependencies += $this->collectDependencies(...$fixture->getDependencies());
        }

        return $dependencies;
    }

    public function allFixtures(): array
    {
        return $this->fixturesLoader->getFixtures();
    }

    public function fixturesToLoad(array $fixtureClasses): array
    {
        $requiredFixtures = [];
        $baseFixtures = $this->baseFixtures();

        foreach ($baseFixtures as $baseFixture) {
            $requiredFixtures += $this->collectDependencies($baseFixture);
        }

        foreach ($fixtureClasses as $class) {
            $requiredFixtures += $this->collectDependencies($class);
        }

        $allFixtures = $this->fixturesLoader->getFixtures();

        $filteredFixtures = [];
        foreach ($allFixtures as $order => $fixture) {
            $fixtureClass = $fixture::class;

            if (isset($requiredFixtures[$fixtureClass])) {
                $filteredFixtures[$order] = $fixture;
            }
        }

        return $filteredFixtures;
    }
}
