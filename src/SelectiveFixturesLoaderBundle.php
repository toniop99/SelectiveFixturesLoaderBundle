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
        $definition->rootNode()
            ->children()
                ->scalarNode('base_fixtures_loader_service_id')
                ->defaultNull()
                ->validate()
                    ->ifTrue(fn($value) => !is_a($value, BaseFixturesLoaderInterface::class, true))
                        ->thenInvalid('The class "%s" configured for "selective_fixtures_loader.base_fixtures_loader_service_id" must implement "' . BaseFixturesLoaderInterface::class . '".')
                    ->end()
                ->info('The service ID (FQCN) of the class implementing BaseFixturesLoaderInterface to provide base fixtures.')
                ->example('App\Services\BaseFixtureUserLoader')
                ->end()
            ->end();
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import('../config/services.php');

        $loaderServiceId = $config['base_fixtures_loader_service_id'];

        if (null !== $loaderServiceId) {
            $builder->setAlias(BaseFixturesLoaderInterface::class, $loaderServiceId);
        }
    }

}
