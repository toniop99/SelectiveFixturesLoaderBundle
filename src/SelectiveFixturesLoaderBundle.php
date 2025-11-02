<?php

declare(strict_types=1);

namespace Andez\SelectiveFixturesLoaderBundle;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

use function class_exists;
use function is_a;

final class SelectiveFixturesLoaderBundle extends AbstractBundle
{
    protected string $extensionAlias = 'andez_selective_fixtures_loader';

    public function configure(DefinitionConfigurator $definition): void
    {
        $rootNode = $definition->rootNode();

        $rootNode
            ->validate()
                ->ifTrue(static fn ($v) => isset($v['base_fixtures_loader_service_id']) && ! empty($v['base_fixtures']))
                ->thenInvalid('Only one of "selective_fixtures_loader.base_fixtures_loader_service_id" or "selective_fixtures_loader.base_fixtures" can be configured at the same time.')
        ->end();

        $rootNode
            ->children()
                ->scalarNode('base_fixtures_loader_service_id')
                    ->defaultNull()
                    ->info('The service ID (FQCN) of the class implementing BaseFixturesLoaderInterface to provide base fixtures.')
                    ->example('App\Services\BaseFixtureUserLoader')
                    ->validate()
                        ->ifTrue(static fn ($value) => $value !== null && ! is_a($value, BaseFixturesLoaderInterface::class, true))
                        ->thenInvalid('The class "%s" configured for "selective_fixtures_loader.base_fixtures_loader_service_id" must implement "' . BaseFixturesLoaderInterface::class . '".')
                    ->end()
                ->end()

                ->arrayNode('base_fixtures')
                    ->info('A list of fixture class names to be used as base fixtures.')
                    ->example(['App\DataFixtures\UserFixtures', 'App\DataFixtures\RoleFixtures'])
                    ->prototype('scalar')
                        ->validate()
                            ->ifTrue(static fn ($value) => ! class_exists($value))
                            ->thenInvalid('The class "%s" configured in "selective_fixtures_loader.base_fixtures" does not exist.')
                        ->end()
                    ->end()
                ->end()

                ->arrayNode('purge_exclusion_tables')
                    ->info('A list of database tables to exclude from purging when loading fixtures.')
                    ->example(['users', 'roles'])
                    ->prototype('scalar')->end()
                    ->defaultValue([])
                ->end()
        ->end();
    }

    /** @inheritDoc */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import('../config/services.php');

        $loaderServiceId  = $config['base_fixtures_loader_service_id'];
        $baseFixturesList = $config['base_fixtures'];

        if ($loaderServiceId !== null) {
            $builder->setAlias(BaseFixturesLoaderInterface::class, $loaderServiceId);
        } elseif (! empty($baseFixturesList)) {
            $definition = $builder->getDefinition(ArrayBaseFixturesLoader::class);
            $definition->setArgument('$baseFixtures', $baseFixturesList);

            $builder->setAlias(BaseFixturesLoaderInterface::class, ArrayBaseFixturesLoader::class);
        }

        $builder->setParameter(
            'andez_selective_fixtures_loader.purge_exclusion_tables',
            $config['purge_exclusion_tables'],
        );
    }
}
