<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Andez\SelectiveFixturesLoaderBundle\ArrayBaseFixturesLoader;
use Andez\SelectiveFixturesLoaderBundle\BaseFixturesLoaderInterface;
use Andez\SelectiveFixturesLoaderBundle\Command\SelectiveFixtureLoaderCommand;
use Andez\SelectiveFixturesLoaderBundle\FixtureCollector;
use Andez\SelectiveFixturesLoaderBundle\FixturesDependencies;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set('andez.selective_fixtures_loader_command', SelectiveFixtureLoaderCommand::class)
            ->args([
                service('doctrine'),
                service('andez.fixture_dependencies'),
            ])
            ->tag('console.command', ['command' => 'andez:selective-fixtures:load'])

        ->set('andez.fixture_dependencies', FixturesDependencies::class)
            ->args([
                service('andez.fixture_collector'),
                service(BaseFixturesLoaderInterface::class)->nullOnInvalid(),
                service('doctrine.fixtures.loader'),
            ])
            ->alias(FixturesDependencies::class, 'andez.fixture_dependencies')

        ->set(ArrayBaseFixturesLoader::class)
            ->arg('$baseFixtures', [])
            ->autowire(false)
            ->autoconfigure(false)
            ->private()

        ->set('andez.fixture_collector', FixtureCollector::class)
            ->args([
                tagged_iterator('doctrine.fixture.orm')
            ])
    ;

};
