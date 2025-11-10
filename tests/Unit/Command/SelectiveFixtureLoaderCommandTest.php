<?php

declare(strict_types=1);

namespace Andez\SelectiveFixturesLoaderBundle\Tests\Unit\Command;

use Andez\SelectiveFixturesLoaderBundle\ArrayBaseFixturesLoader;
use Andez\SelectiveFixturesLoaderBundle\Command\SelectiveFixtureLoaderCommand;
use Andez\SelectiveFixturesLoaderBundle\FixturesDependencies;
use Andez\SelectiveFixturesLoaderBundle\Tests\Unit\Command\Fixtures\TrackingProductFixture;
use Andez\SelectiveFixturesLoaderBundle\Tests\Unit\Command\Fixtures\TrackingProfileFixture;
use Andez\SelectiveFixturesLoaderBundle\Tests\Unit\Command\Fixtures\TrackingRoleFixture;
use Andez\SelectiveFixturesLoaderBundle\Tests\Unit\Command\Fixtures\TrackingStore;
use Andez\SelectiveFixturesLoaderBundle\Tests\Unit\Command\Fixtures\TrackingUserFixture;
use Doctrine\Bundle\FixturesBundle\Loader\SymfonyFixturesLoader;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMSetup;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

use function sys_get_temp_dir;

final class SelectiveFixtureLoaderCommandTest extends TestCase
{
    private function createEntityManager(): EntityManagerInterface
    {
        $config = ORMSetup::createAttributeMetadataConfig([__DIR__], true);
        $config->setProxyDir(sys_get_temp_dir());
        $config->setProxyNamespace('Proxies');
        $connection = DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ], $config);

        return new EntityManager($connection, $config);
    }

    private function createRegistry(EntityManagerInterface $em): ManagerRegistry
    {
        return new class ($em) implements ManagerRegistry {
            public function __construct(private readonly EntityManagerInterface $em)
            {
            }

            public function getDefaultConnectionName(): string
            {
                return 'default';
            }

            public function getConnection(string|null $name = null): Connection
            {
                return $this->em->getConnection();
            }

            /** @return array<string, Connection> */
            public function getConnections(): array
            {
                return ['default' => $this->em->getConnection()];
            }

            /** @return array<string, string> */
            public function getConnectionNames(): array
            {
                return ['default' => 'default'];
            }

            public function getDefaultManagerName(): string
            {
                return 'default';
            }

            public function getManager(string|null $name = null): ObjectManager
            {
                return $this->em;
            }

            /** @return array<string, ObjectManager> */
            public function getManagers(): array
            {
                return ['default' => $this->em];
            }

            public function resetManager(string|null $name = null): ObjectManager
            {
                return $this->em;
            }

            /** @return array<string, string> */
            public function getManagerNames(): array
            {
                return ['default' => 'default'];
            }

            public function getRepository(string $persistentObject, string|null $persistentManagerName = null): ObjectRepository
            {
                return $this->em->getRepository($persistentObject);
            }

            public function getManagerForClass(string $class): ObjectManager
            {
                return $this->em;
            }
        };
    }

    private function buildFixturesLoader(): SymfonyFixturesLoader
    {
        $loader = new SymfonyFixturesLoader();
        $loader->addFixture(new TrackingProductFixture());
        $loader->addFixture(new TrackingRoleFixture());
        $loader->addFixture(new TrackingUserFixture());
        $loader->addFixture(new TrackingProfileFixture());

        return $loader;
    }

    private function createCommand(ArrayBaseFixturesLoader|null $baseLoader = null): SelectiveFixtureLoaderCommand
    {
        $em                   = $this->createEntityManager();
        $registry             = $this->createRegistry($em);
        $fixturesLoader       = $this->buildFixturesLoader();
        $fixturesDependencies = new FixturesDependencies($fixturesLoader, $baseLoader, []);

        return new SelectiveFixtureLoaderCommand($registry, $fixturesDependencies);
    }

    protected function setUp(): void
    {
        parent::setUp();

        TrackingStore::reset();
    }

    public function testExecuteLoadsSpecifiedFixturesWithDependenciesInOrder(): void
    {
        $command  = $this->createCommand();
        $tester   = new CommandTester($command);
        $exitCode = $tester->execute([
            '--fixtures' => [TrackingProfileFixture::class],
            '--append' => true,
            '--em' => 'default',
        ]);

        self::assertSame(0, $exitCode);
        self::assertSame([
            TrackingRoleFixture::class,
            TrackingUserFixture::class,
            TrackingProfileFixture::class,
        ], TrackingStore::$loaded);
    }

    public function testExecuteDryRunDoesNotInvokeFixtureLoad(): void
    {
        $command  = $this->createCommand();
        $tester   = new CommandTester($command);
        $exitCode = $tester->execute([
            '--fixtures' => [TrackingProfileFixture::class],
            '--dry-run' => true,
            '--em' => 'default',
        ]);

        self::assertSame(0, $exitCode);

        self::assertSame([
            TrackingRoleFixture::class,
            TrackingUserFixture::class,
            TrackingProfileFixture::class,
        ], TrackingStore::$loaded);
        self::assertStringContainsString('(dry-run)', $tester->getDisplay());
    }

    public function testExecuteAbortsOnNegativeConfirmation(): void
    {
        $command = $this->createCommand();
        $tester  = new CommandTester($command);
        $tester->setInputs(['no']);
        $exitCode = $tester->execute([
            '--fixtures' => [TrackingProfileFixture::class],
            '--em' => 'default',
        ], ['interactive' => true]);

        self::assertSame(0, $exitCode);
        self::assertSame([], TrackingStore::$loaded);
        self::assertStringContainsString('will be purged', $tester->getDisplay());
    }

    public function testExecuteIncludesBaseFixturesDependencies(): void
    {
        $baseLoader = new ArrayBaseFixturesLoader([TrackingProductFixture::class]);
        $command    = $this->createCommand($baseLoader);
        $tester     = new CommandTester($command);
        $exitCode   = $tester->execute([
            '--fixtures' => [TrackingProfileFixture::class],
            '--append' => true,
            '--em' => 'default',
        ]);

        self::assertSame(0, $exitCode);
        self::assertSame([
            TrackingProductFixture::class,
            TrackingRoleFixture::class,
            TrackingUserFixture::class,
            TrackingProfileFixture::class,
        ], TrackingStore::$loaded);
    }

    public function testInteractiveSelectionLoadsChosenFixtureAndDependencies(): void
    {
        $command = $this->createCommand();
        $tester  = new CommandTester($command);
        $tester->setInputs(['yes', TrackingProfileFixture::class]);
        $exitCode = $tester->execute([
            '--em' => 'default',
            '--append' => true,
        ], ['interactive' => true]);

        self::assertSame(0, $exitCode);
        self::assertSame([
            TrackingRoleFixture::class,
            TrackingUserFixture::class,
            TrackingProfileFixture::class,
        ], TrackingStore::$loaded);
        self::assertStringContainsString('Select fixtures to load', $tester->getDisplay());
    }

    public function testExecuteProceedsOnPositiveConfirmation(): void
    {
        $command = $this->createCommand();
        $tester  = new CommandTester($command);
        $tester->setInputs(['yes']);
        $exitCode = $tester->execute([
            '--fixtures' => [TrackingProfileFixture::class],
            '--em' => 'default',
        ], ['interactive' => true]);

        self::assertSame(0, $exitCode);
        self::assertSame([
            TrackingRoleFixture::class,
            TrackingUserFixture::class,
            TrackingProfileFixture::class,
        ], TrackingStore::$loaded);
    }
}
