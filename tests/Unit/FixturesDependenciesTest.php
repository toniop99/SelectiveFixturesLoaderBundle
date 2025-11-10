<?php

declare(strict_types=1);

namespace Andez\SelectiveFixturesLoaderBundle\Tests\Unit;

use Andez\SelectiveFixturesLoaderBundle\FixturesDependencies;
use Andez\SelectiveFixturesLoaderBundle\Tests\Unit\Fixtures\GroupFixture;
use Andez\SelectiveFixturesLoaderBundle\Tests\Unit\Fixtures\ProfileFixture;
use Andez\SelectiveFixturesLoaderBundle\Tests\Unit\Fixtures\RoleFixture;
use Andez\SelectiveFixturesLoaderBundle\Tests\Unit\Fixtures\UserFixture;
use Doctrine\Bundle\FixturesBundle\Loader\SymfonyFixturesLoader;
use PHPUnit\Framework\TestCase;

use function array_map;
use function array_values;

final class FixturesDependenciesTest extends TestCase
{
    private function buildLoader(): SymfonyFixturesLoader
    {
        $loader = new SymfonyFixturesLoader();

        $loader->addFixture(new RoleFixture());
        $loader->addFixture(new UserFixture());
        $loader->addFixture(new ProfileFixture());
        $loader->addFixture(new GroupFixture());

        return $loader;
    }

    public function testAllFixturesReturnsAllInOrderedSequence(): void
    {
        $loader   = $this->buildLoader();
        $sut      = new FixturesDependencies($loader, null, []);
        $fixtures = $sut->allFixtures();

        $classes = array_map(static fn ($f) => $f::class, $fixtures);

        self::assertSame([
            RoleFixture::class,
            UserFixture::class,
            ProfileFixture::class,
            GroupFixture::class,
        ], $classes, 'Expected dependency order propagation.');
    }

    public function testFixturesToLoadWithNoBaseAndOneTargetIncludesDependencies(): void
    {
        $loader = $this->buildLoader();
        $sut    = new FixturesDependencies($loader, null, []);

        $fixtures = $sut->fixturesToLoad([ProfileFixture::class]);
        $classes  = array_values(array_map(static fn ($f) => $f::class, $fixtures));

        // Profile depends on User which depends on Role. Ordering should preserve original load order
        self::assertSame([
            RoleFixture::class,
            UserFixture::class,
            ProfileFixture::class,
        ], $classes);
    }

    public function testFixturesToLoadWithBaseFixturesMergesDependenciesOnlyOnce(): void
    {
        $loader = $this->buildLoader();
        $base   = new DummyBaseFixturesLoader([UserFixture::class]);
        $sut    = new FixturesDependencies($loader, $base, []);

        $fixtures = $sut->fixturesToLoad([GroupFixture::class]);
        $classes  = array_values(array_map(static fn ($f) => $f::class, $fixtures));

        // Should include Role (dependency of both), User (base), Group
        self::assertSame([
            RoleFixture::class,
            UserFixture::class,
            GroupFixture::class,
        ], $classes);
    }

    public function testFixturesToLoadMultipleTargetsAggregateTransitiveDependencies(): void
    {
        $loader = $this->buildLoader();
        $sut    = new FixturesDependencies($loader, null, []);

        $fixtures = $sut->fixturesToLoad([GroupFixture::class, ProfileFixture::class]);
        $classes  = array_values(array_map(static fn ($f) => $f::class, $fixtures));

        // Both branches share Role; Profile adds User
        self::assertSame([
            RoleFixture::class,
            UserFixture::class,
            ProfileFixture::class,
            GroupFixture::class,
        ], $classes);
    }

    public function testPurgeExclusionTablesReturnsConfiguredList(): void
    {
        $loader = $this->buildLoader();
        $sut    = new FixturesDependencies($loader, null, ['doctrine_migration_versions', 'audit_log']);

        self::assertSame(['doctrine_migration_versions', 'audit_log'], $sut->purgeExclusionTables());
    }
}
