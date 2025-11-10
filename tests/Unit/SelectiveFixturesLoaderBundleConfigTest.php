<?php

declare(strict_types=1);

namespace Andez\SelectiveFixturesLoaderBundle\Tests\Unit;

use Andez\SelectiveFixturesLoaderBundle\ArrayBaseFixturesLoader;
use Andez\SelectiveFixturesLoaderBundle\BaseFixturesLoaderInterface;
use Andez\SelectiveFixturesLoaderBundle\SelectiveFixturesLoaderBundle;
use Andez\SelectiveFixturesLoaderBundle\Tests\Unit\Fixtures\RoleFixture;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use function dirname;
use function sys_get_temp_dir;

final class SelectiveFixturesLoaderBundleConfigTest extends TestCase
{
    /**
     * @param array<string, mixed> $raw
     *
     * @return array<string, mixed>
     */
    private function processRaw(array $raw): array
    {
        $bundle    = new SelectiveFixturesLoaderBundle();
        $extension = $bundle->getContainerExtension();

        $configuration = $extension->getConfiguration([], new ContainerBuilder());
        self::assertInstanceOf(ConfigurationInterface::class, $configuration);

        $processor = new Processor();

        return $processor->processConfiguration($configuration, [$raw]);
    }

    private function createContainerBuilder(): ContainerBuilder
    {
        $builder = new ContainerBuilder();
        $builder->setParameter('kernel.environment', 'test');
        $builder->setParameter('kernel.debug', true);
        $builder->setParameter('kernel.project_dir', dirname(__DIR__, 2));
        $tmp = sys_get_temp_dir();
        $builder->setParameter('kernel.cache_dir', $tmp . '/sf_cache');
        $builder->setParameter('kernel.build_dir', $tmp . '/sf_build');

        return $builder;
    }

    public function testConfigurationMutualExclusionValidation(): void
    {
        $this->expectExceptionMessage('Only one of');
        $this->processRaw([
            'base_fixtures_loader_service_id' => DummyBaseFixturesLoader::class,
            'base_fixtures' => [RoleFixture::class],
        ]);
    }

    public function testConfigurationValidWithLoaderService(): void
    {
        $processed = $this->processRaw([
            'base_fixtures_loader_service_id' => DummyBaseFixturesLoader::class,
            'purge_exclusion_tables' => ['t1'],
        ]);

        self::assertSame(DummyBaseFixturesLoader::class, $processed['base_fixtures_loader_service_id']);
        self::assertSame(['t1'], $processed['purge_exclusion_tables']);
    }

    public function testConfigurationValidWithBaseFixturesArray(): void
    {
        $processed = $this->processRaw([
            'base_fixtures' => [RoleFixture::class],
            'purge_exclusion_tables' => ['t1','t2'],
        ]);

        self::assertSame(['t1', 't2'], $processed['purge_exclusion_tables']);
        self::assertSame([RoleFixture::class], $processed['base_fixtures']);
    }

    public function testLoadExtensionSetsAliasForLoaderService(): void
    {
        $bundle  = new SelectiveFixturesLoaderBundle();
        $builder = $this->createContainerBuilder();
        $builder->register(DummyBaseFixturesLoader::class, DummyBaseFixturesLoader::class)->setArguments([[]]);
        $extension = $bundle->getContainerExtension();
        $extension->load([
            [
                'base_fixtures_loader_service_id' => DummyBaseFixturesLoader::class,
                'base_fixtures' => [],
                'purge_exclusion_tables' => ['keep_table'],
            ],
        ], $builder);

        self::assertTrue($builder->hasAlias(BaseFixturesLoaderInterface::class));
        self::assertSame(DummyBaseFixturesLoader::class, (string) $builder->getAlias(BaseFixturesLoaderInterface::class));
        self::assertSame(['keep_table'], $builder->getParameter('andez_selective_fixtures_loader.purge_exclusion_tables'));
    }

    public function testLoadExtensionSetsArrayLoaderWhenBaseFixturesProvided(): void
    {
        $bundle    = new SelectiveFixturesLoaderBundle();
        $builder   = $this->createContainerBuilder();
        $extension = $bundle->getContainerExtension();
        $extension->load([
            [
                'base_fixtures_loader_service_id' => null,
                'base_fixtures' => [RoleFixture::class],
                'purge_exclusion_tables' => [],
            ],
        ], $builder);

        self::assertTrue($builder->hasAlias(BaseFixturesLoaderInterface::class));
        self::assertSame(ArrayBaseFixturesLoader::class, (string) $builder->getAlias(BaseFixturesLoaderInterface::class));
        $definition = $builder->getDefinition(ArrayBaseFixturesLoader::class);
        self::assertSame([RoleFixture::class], $definition->getArgument('$baseFixtures'));
    }
}
