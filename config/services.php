<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Andez\SelectiveFixturesLoaderBundle\ArrayBaseFixturesLoader;
use Andez\SelectiveFixturesLoaderBundle\BaseFixturesLoaderInterface;
use Andez\SelectiveFixturesLoaderBundle\Command\SelectiveFixtureLoaderCommand;
use Andez\SelectiveFixturesLoaderBundle\FixturesDependencies;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set('andez.selective_fixtures_loader_command', SelectiveFixtureLoaderCommand::class)
            ->args([
                service('doctrine'),
                service('andez.fixture_dependencies'),
            ])
            ->tag('console.command', ['command' => 'andez:selective-fixtures:load'])

        ->set('andez.fixture_dependencies', FixturesDependencies::class)
            ->args([
                service('doctrine.fixtures.loader'),
                service(BaseFixturesLoaderInterface::class)->nullOnInvalid(),
                param('andez_selective_fixtures_loader.purge_exclusion_tables'),
            ])
            ->alias(FixturesDependencies::class, 'andez.fixture_dependencies')

        ->set(ArrayBaseFixturesLoader::class)
            ->arg('$baseFixtures', [])
            ->autowire(false)
            ->autoconfigure(false)
            ->private();
};
