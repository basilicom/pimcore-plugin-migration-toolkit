<?php

namespace Basilicom\PimcorePluginMigrationToolkit\Command;

use Basilicom\PimcorePluginMigrationToolkit\Trait\ClearCacheTrait;
use Pimcore;
use Pimcore\Console\AbstractCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

#[AsCommand('basilicom:migrations:migrate-in-separate-processes', 'Executes the same migrations as the doctrine:migrations:execute command, but each one is run in a separate process, to prevent problems with PHP classes that changed during the runtime.')]
class MigrateInSeparateProcessesCommand extends AbstractCommand
{
    use ClearCacheTrait;

    private const string OPTION_BUNDLE = 'bundle';
    private const string OPTION_TIMEOUT = 'timeout';
    private const string LOG_EMPTY_LINE = '                                                            ';
    private const string LOG_SEPARATOR_LINE = '<info>======================================================================================</info>';

    protected function configure(): void
    {
        $this
            ->addOption(
                self::OPTION_BUNDLE,
                'b',
                InputOption::VALUE_OPTIONAL,
                'The bundle which should be migrated',
                null
            )
            ->addOption(
                self::OPTION_TIMEOUT,
                't',
                InputOption::VALUE_OPTIONAL,
                'An optional timeout to allow execution of very huge migrations. Set "0" to disable timeout.',
                120
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $bundle = $input->getOption(self::OPTION_BUNDLE);
        $timeout = (int) $input->getOption(self::OPTION_TIMEOUT);

        $this->clearCache();

        // The following prevents problems when the container changes during runtime - which is the case with migrations
        $eventDispatcher = Pimcore::getEventDispatcher();
        foreach ($eventDispatcher->getListeners(ConsoleEvents::TERMINATE) as $listener) {
            $eventDispatcher->removeListener(ConsoleEvents::TERMINATE, $listener);
        }

        $idleMigrations = $this->getIdleMigrations($bundle);

        if (count($idleMigrations) < 1) {
            $output->writeln('<error>No migrations to execute</error>');
            return self::SUCCESS;
        }

        $output->writeln(self::LOG_EMPTY_LINE);
        $output->writeln(self::LOG_SEPARATOR_LINE);
        $output->writeln('                           Following migrations will be executed:                           ');
        $output->writeln(self::LOG_SEPARATOR_LINE);
        $output->writeln(' > ' . implode(PHP_EOL . ' > ', $idleMigrations));

        if ($timeout <= 0) {
            $output->writeln(PHP_EOL . '<comment>⚠️ Migration timeout has been disabled.</comment>' . PHP_EOL);
        }

        foreach ($idleMigrations as $migration) {
            $migrationVersion = substr($migration, strrpos($migration, '\\') + 1);
            $migrationPrefix = substr($migration, 0, strrpos($migration, '\\'));
            $output->writeln(self::LOG_EMPTY_LINE);
            $output->writeln(self::LOG_SEPARATOR_LINE);
            $output->writeln('        Executing the migration ' . $migrationVersion . ' (' . $migrationPrefix . ')        ');
            $output->writeln(self::LOG_SEPARATOR_LINE);

            $process = new Process(
                ['bin/console', 'doctrine:migrations:execute', '--no-interaction', '--ignore-maintenance-mode', $migration],
                PIMCORE_PROJECT_ROOT
            );
            $process->setTimeout($timeout > 0 ? $timeout : null);
            $process->run(
                function ($type, $buffer) use ($output) {
                    if (Process::ERR === $type) {
                        $output->writeln('<error>' . $buffer . '</error>');
                    } else {
                        $output->writeln($buffer);
                    }
                }
            );

            if ($process->getExitCode() !== 0) {
                return self::FAILURE;
            }
        }

        $output->writeln(self::LOG_EMPTY_LINE);
        $output->writeln(self::LOG_SEPARATOR_LINE);
        $output->writeln('<info>                                  Migrations finished                                  </info>');
        $output->writeln(self::LOG_SEPARATOR_LINE);
        $output->writeln(self::LOG_EMPTY_LINE);

        return self::SUCCESS;
    }

    protected function getIdleMigrations(?string $bundle = null): array
    {
        $command = $bundle
            ? sprintf(
                'bin/console --ignore-maintenance-mode doctrine:migrations:list --prefix="%s" | grep "not migrated" | cut -d"|" -f2 | awk \'{$1=$1};1\'',
                $bundle
            )
            : 'bin/console --ignore-maintenance-mode doctrine:migrations:list | grep "not migrated" | cut -d"|" -f2 | awk \'{$1=$1};1\'';

        $process = Process::fromShellCommandline($command, PIMCORE_PROJECT_ROOT);
        $process->run();
        $idleMigrations = explode(PHP_EOL, $process->getOutput());

        return array_filter($idleMigrations);
    }
}
