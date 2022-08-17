<?php

namespace Basilicom\PimcorePluginMigrationToolkit\Command;

use Pimcore;
use Pimcore\Console\AbstractCommand;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class MigrateInSeparateProcessesCommand extends AbstractCommand
{
    const OPTION_TIMEOUT = 'timeout';
    const LOG_EMPTY_LINE = '                                                            ';
    const LOG_SEPARATOR_LINE = '<info>======================================================================================</info>';

    protected static $defaultName = 'basilicom:migrations:migrate-in-separate-processes';

    protected function configure()
    {
        $this
            ->setDescription(
                'Executes the same migrations as the pimcore:migrations:migrate command, ' .
                'but each one is run in a separate process, to prevent problems with PHP classes that changed during the runtime.'
            )
            ->addOption(
                self::OPTION_TIMEOUT,
                't',
                InputOption::VALUE_OPTIONAL,
                'An optional timeout to allow execution of very huge migrations. Set "0" to disable timeout.',
                120
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $timeout = (int) $input->getOption(self::OPTION_TIMEOUT);

        // The following prevents problems when the container changes during runtime - which is the case with migrations
        $eventDispatcher = Pimcore::getKernel()->getContainer()->get('event_dispatcher');
        foreach ($eventDispatcher->getListeners(ConsoleEvents::TERMINATE) as $listener) {
            $eventDispatcher->removeListener(ConsoleEvents::TERMINATE, $listener);
        }

        $idleMigrations = $this->getIdleMigrations();
        if (count($idleMigrations) < 1) {
            $output->writeln('<error>No migrations to execute</error>');
            exit(0);
        }

        $output->writeln(self::LOG_EMPTY_LINE);
        $output->writeln(self::LOG_SEPARATOR_LINE);
        $output->writeln('                           Following migrations will be executed:                           ');
        $output->writeln(self::LOG_SEPARATOR_LINE);
        $output->writeln(' > ' . implode(PHP_EOL . ' > ', $idleMigrations));

        foreach ($idleMigrations as $migration) {
            $output->writeln(self::LOG_EMPTY_LINE);
            $output->writeln(self::LOG_SEPARATOR_LINE);
            $output->writeln('                Executing the migration ' . $migration);
            $output->writeln(self::LOG_SEPARATOR_LINE);

            $process = new Process(
                ['bin/console', 'pimcore:migrations:execute', $migration, '--no-interaction'],
                PIMCORE_PROJECT_ROOT
            );
            $process->setTimeout($timeout > 0 ? $timeout : null);
            $process->run(function ($type, $buffer) use ($output) {
                if (Process::ERR === $type) {
                    $output->writeln('<error>' . $buffer . '</error>');
                } else {
                    $output->writeln($buffer);
                }
            });

            if ($process->getExitCode() !== 0) {
                exit(1);
            }
        }

        $output->writeln(self::LOG_EMPTY_LINE);
        $output->writeln(self::LOG_SEPARATOR_LINE);
        $output->writeln('<info>                                  Migrations finished                                  </info>');
        $output->writeln(self::LOG_SEPARATOR_LINE);
        $output->writeln(self::LOG_EMPTY_LINE);
    }

    protected function getIdleMigrations(): array
    {
        $process = new Process(
            'bin/console pimcore:migrations:status --show-versions --no-interaction | grep "not migrated" | awk \'{print substr($4, 2, length($4) - 2) }\'',
            PIMCORE_PROJECT_ROOT
        );

        $process->run();

        $idleMigrations = [];
        foreach ($process as $type => $psOutputLine) {
            if ($type === 'out') {
                $idleMigrations = explode(PHP_EOL, $psOutputLine);
            }
        }

        return array_filter($idleMigrations);
    }
}
