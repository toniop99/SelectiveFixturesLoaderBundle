<?php

declare(strict_types=1);

namespace Andez\SelectiveFixturesLoaderBundle;

use Doctrine\Bundle\FixturesBundle\Loader\SymfonyFixturesLoader;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;

final readonly class FixturesDependencies
{
    /** @param string[] $purgeExclusionTables */
    public function __construct(
        private BaseFixturesLoaderInterface|null $baseFixturesLoader = null,
        private SymfonyFixturesLoader $fixturesLoader,
        private array $purgeExclusionTables = [],
    ) {
    }

    /** @return class-string[] */
    private function baseFixtures(): array
    {
        if ($this->baseFixturesLoader === null) {
            return [];
        }

        return $this->baseFixturesLoader->getBaseFixtures();
    }

    /**
     * @param class-string ...$fixtureClass
     *
     * @return array<class-string, true>
     */
    private function collectDependencies(string ...$fixtureClass): array
    {
        $dependencies = [];

        foreach ($fixtureClass as $class) {
            $dependencies[$class] = true;
            $fixture              = $this->fixturesLoader->getFixture($class);

            if (! $fixture instanceof DependentFixtureInterface) {
                continue;
            }

            $dependencies += $this->collectDependencies(...$fixture->getDependencies());
        }

        return $dependencies;
    }

    /** @return FixtureInterface[] */
    public function allFixtures(): array
    {
        return $this->fixturesLoader->getFixtures();
    }

    /**
     * @param class-string<FixtureInterface>[] $fixtureClasses
     *
     * @return FixtureInterface[]
     */
    public function fixturesToLoad(array $fixtureClasses): array
    {
        $requiredFixtures = [];
        $baseFixtures     = $this->baseFixtures();

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

            if (! isset($requiredFixtures[$fixtureClass])) {
                continue;
            }

            $filteredFixtures[$order] = $fixture;
        }

        return $filteredFixtures;
    }

    /** @return string[] */
    public function purgeExclusionTables(): array
    {
        return $this->purgeExclusionTables;
    }
}
