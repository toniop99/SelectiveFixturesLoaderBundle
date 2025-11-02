<?php

namespace Andez\SelectiveFixturesLoaderBundle;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

final class SelectiveFixturesLoaderBundle extends AbstractBundle
{
    protected string $extensionAlias = 'andez_selective_fixtures_loader';

    public function configure(DefinitionConfigurator $definition): void
    {
        $rootNode = $definition->rootNode();

        $rootNode
            ->validate()
                ->ifTrue(fn($v) => isset($v['base_fixtures_loader_service_id']) && !empty($v['base_fixtures']))
                ->thenInvalid('Only one of "selective_fixtures_loader.base_fixtures_loader_service_id" or "selective_fixtures_loader.base_fixtures" can be configured at the same time.')
        ->end();


        $rootNode
            ->children()
                ->scalarNode('base_fixtures_loader_service_id')
                ->defaultNull()
                ->validate()
                    ->ifTrue(fn($value) => null !== $value && !is_a($value, BaseFixturesLoaderInterface::class, true))
                    ->thenInvalid('The class "%s" configured for "selective_fixtures_loader.base_fixtures_loader_service_id" must implement "' . BaseFixturesLoaderInterface::class . '".')
                ->end()
                ->info('The service ID (FQCN) of the class implementing BaseFixturesLoaderInterface to provide base fixtures.')
                ->example('App\Services\BaseFixtureUserLoader')
            ->end()
            ->arrayNode('base_fixtures')
                ->info('A list of fixture class names to be used as base fixtures.')
                ->example(['App\DataFixtures\UserFixtures', 'App\DataFixtures\RoleFixtures'])
                ->prototype('scalar')
                ->validate()
                    ->ifTrue(fn($value) => !class_exists($value))
                    ->thenInvalid('The class "%s" configured in "selective_fixtures_loader.base_fixtures" does not exist.')
                ->end()
            ->end()
        ->end()

        ->end();
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import('../config/services.php');

        $loaderServiceId = $config['base_fixtures_loader_service_id'];
        $baseFixturesList = $config['base_fixtures'];

        if (null !== $loaderServiceId) {
            $builder->setAlias(BaseFixturesLoaderInterface::class, $loaderServiceId);
        } elseif (!empty($baseFixturesList)) {
            $definition = $builder->getDefinition(ArrayBaseFixturesLoader::class);
            $definition->setArgument('$baseFixtures', $baseFixturesList);

            $builder->setAlias(BaseFixturesLoaderInterface::class, ArrayBaseFixturesLoader::class);
        }
    }

}
