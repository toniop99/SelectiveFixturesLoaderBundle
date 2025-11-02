<?php

namespace Andez\SelectiveFixturesLoaderBundle\Command;

use Andez\SelectiveFixturesLoaderBundle\FixturesDependencies;
use Doctrine\Bundle\FixturesBundle\Purger\ORMPurgerFactory;
use Doctrine\Common\DataFixtures\Executor\DryRunORMExecutor;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\AbstractLogger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Stringable;

class SelectiveFixtureLoaderCommand extends Command
{

    public function __construct(
        private ManagerRegistry      $doctrine,
        private FixturesDependencies $getFixtureDependencies,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('andez:selective-fixtures:load')
            ->setDescription('Load selective data fixtures to your database for development or testing purposes')
            ->addOption('append', null, InputOption::VALUE_NONE, 'Append the data fixtures instead of deleting all data from the database first.')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Load the fixtures as a dry run.')
            ->addOption('purge-exclusions', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'List of database tables to ignore while purging')
            ->addOption('purge-with-truncate', null, InputOption::VALUE_NONE, 'Purge data by using a database-level TRUNCATE statement')
            ->addOption('em', null, InputOption::VALUE_REQUIRED, 'The entity manager to use for this command.')
            ->addOption('fixture', null, InputOption::VALUE_REQUIRED, 'Fixture class name to load (FQCN).')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $ui = new SymfonyStyle($input, $output);
        $em = $this->doctrine->getManager($input->getOption('em'));

        assert($em instanceof EntityManagerInterface);

//        if (! $input->getOption('dry-run') && ! $input->getOption('append')) {
//            if (! $ui->confirm(sprintf('Careful, database "%s" will be purged. Do you want to continue?', $em->getConnection()->getDatabase()), ! $input->isInteractive())) {
//                return Command::SUCCESS;
//            }
//        }


        $selectedFixture = $input->getOption('fixture');
        if (empty($selectedFixture)) {
            $fixtures = $this->getFixtureDependencies->allFixtures();

            $selectedFixture = $ui->choice(
                'Select fixtures to load (multiple selections allowed, separate by comma)',
                array_map(fn($fixture) => get_class($fixture), $fixtures),
                null,
                false
            );
        }

        $fixturesToLoad = $this->getFixtureDependencies->fixturesToLoad([$selectedFixture]);

        $factory = new ORMPurgerFactory();

        $purger = $factory->createForEntityManager(
            $input->getOption('em'),
            $em,
            $input->getOption('purge-exclusions'),
            (bool) $input->getOption('purge-with-truncate')
        );

        if ($input->getOption('dry-run')) {
            $ui->text('  <comment>(dry-run)</comment>');
            $executor = new DryRunORMExecutor($em, $purger);
        } else {
            $executor = new ORMExecutor($em, $purger);
        }

        $executor->setLogger(new class ($ui) extends AbstractLogger {
            public function __construct(private readonly SymfonyStyle $ui)
            {
            }

            /** {@inheritDoc} */
            public function log(mixed $level, string|Stringable $message, array $context = []): void
            {
                $this->ui->text(sprintf('  <comment>></comment> <info>%s</info>', $message));
            }
        });

        $executor->execute($fixturesToLoad, $input->getOption('append'));

        return Command::SUCCESS;
    }

}
