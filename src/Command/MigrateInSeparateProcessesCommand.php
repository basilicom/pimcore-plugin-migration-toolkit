<?php

declare(strict_types=1);

namespace Basilicom\PimcorePluginMigrationToolkit\Command;

use Doctrine\Migrations\DependencyFactory;
use Pimcore;
use Pimcore\Console\AbstractCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * Runs every pending Doctrine migration in its own PHP process. A migration that changes class
 * definitions or the container would otherwise leave the following migrations of the same run
 * with stale classes.
 */
#[AsCommand(
    name: self::NAME,
    description: 'Executes (or reverts) Doctrine migrations one PHP process each, so no migration sees classes another one changed in the same run.',
)]
class MigrateInSeparateProcessesCommand extends AbstractCommand
{
    public const string NAME          = 'basilicom:migrations:migrate-in-separate-processes';
    public const string DOWN_PREVIOUS = 'prev';

    private const string OPTION_BUNDLE  = 'bundle';
    private const string OPTION_TIMEOUT = 'timeout';
    private const string OPTION_DRY_RUN = 'dry-run';
    private const string OPTION_DOWN    = 'down';
    private const int DEFAULT_TIMEOUT   = 120;

    public function __construct(
        #[Autowire(service: 'doctrine.migrations.dependency_factory')]
        private readonly DependencyFactory $dependencyFactory,
        #[Autowire(param: 'kernel.project_dir')]
        private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                self::OPTION_BUNDLE,
                'b',
                InputOption::VALUE_REQUIRED,
                'Only migrations whose class name starts with this prefix, e.g. "App" or "Pimcore"',
            )
            ->addOption(
                self::OPTION_TIMEOUT,
                't',
                InputOption::VALUE_REQUIRED,
                'Seconds a single migration process may take; 0 disables the timeout',
                self::DEFAULT_TIMEOUT,
            )
            ->addOption(self::OPTION_DRY_RUN, null, InputOption::VALUE_NONE, 'Only list the migrations that would run')
            ->addOption(
                self::OPTION_DOWN,
                null,
                InputOption::VALUE_REQUIRED,
                sprintf('Revert instead of execute: "%s" for the latest executed migration or a version to revert down to (inclusive)', self::DOWN_PREVIOUS),
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $prefix  = $input->getOption(self::OPTION_BUNDLE) ?: null;
        $timeout = (int) $input->getOption(self::OPTION_TIMEOUT);
        $down    = $input->getOption(self::OPTION_DOWN);

        $this->removeTerminateListeners();

        $versions = $down === null ? $this->pendingVersions($prefix) : $this->versionsToRevert($prefix, (string) $down);
        if ($versions === []) {
            $this->io->info('No migrations to execute.');

            return self::SUCCESS;
        }

        $this->io->section(sprintf('%d migration(s) will be %s', count($versions), $down === null ? 'executed' : 'reverted'));
        $this->io->listing($versions);

        if ($timeout <= 0) {
            $this->io->comment('Migration timeout disabled.');
        }

        if ($input->getOption(self::OPTION_DRY_RUN)) {
            return self::SUCCESS;
        }

        foreach ($versions as $version) {
            $this->io->section(sprintf('%s %s', $down === null ? 'Executing' : 'Reverting', $version));

            $process = $this->migrationProcess($version, $down !== null, $timeout);
            $process->run(function (string $type, string $buffer) use ($output): void {
                $output->write($type === Process::ERR ? sprintf('<error>%s</error>', $buffer) : $buffer);
            });

            if (!$process->isSuccessful()) {
                $this->io->error(sprintf('Migration %s failed with exit code %d.', $version, $process->getExitCode() ?? -1));

                return self::FAILURE;
            }
        }

        $this->io->success('Migrations finished.');

        return self::SUCCESS;
    }

    /** @return array<string> in execution order */
    private function pendingVersions(?string $prefix): array
    {
        $versions = [];
        foreach ($this->dependencyFactory->getMigrationStatusCalculator()->getNewMigrations()->getItems() as $migration) {
            $versions[] = (string) $migration->getVersion();
        }

        return $this->filterByPrefix($versions, $prefix);
    }

    /** @return array<string> newest first, down to and including the requested version */
    private function versionsToRevert(?string $prefix, string $target): array
    {
        $storage = $this->dependencyFactory->getMetadataStorage();
        $storage->ensureInitialized();

        $executed = [];
        foreach ($storage->getExecutedMigrations()->getItems() as $migration) {
            $executed[] = (string) $migration->getVersion();
        }
        $executed = array_reverse($this->filterByPrefix($executed, $prefix));

        if ($target === self::DOWN_PREVIOUS) {
            return array_slice($executed, 0, 1);
        }

        $position = array_search($target, $executed, true);
        if ($position === false) {
            throw new InvalidArgumentException(sprintf(
                'Migration "%s" has not been executed%s.',
                $target,
                $prefix === null ? '' : sprintf(' (prefix "%s")', $prefix),
            ));
        }

        return array_slice($executed, 0, $position + 1);
    }

    /**
     * @param array<string> $versions
     *
     * @return array<string>
     */
    private function filterByPrefix(array $versions, ?string $prefix): array
    {
        if ($prefix === null) {
            return $versions;
        }

        return array_values(array_filter($versions, static fn (string $version): bool => str_starts_with($version, $prefix)));
    }

    private function migrationProcess(string $version, bool $down, int $timeout): Process
    {
        $process = new Process(
            [
                (new PhpExecutableFinder())->find() ?: 'php',
                $this->projectDir . '/bin/console',
                'doctrine:migrations:execute',
                '--no-interaction',
                '--ignore-maintenance-mode',
                $down ? '--down' : '--up',
                $version,
            ],
            $this->projectDir,
        );
        $process->setTimeout($timeout > 0 ? $timeout : null);

        return $process;
    }

    /**
     * The child processes may change class definitions and the container; Pimcore's own terminate
     * listeners in this parent process would then run against a stale state.
     */
    private function removeTerminateListeners(): void
    {
        $eventDispatcher = Pimcore::getEventDispatcher();
        foreach ($eventDispatcher->getListeners(ConsoleEvents::TERMINATE) as $listener) {
            $eventDispatcher->removeListener(ConsoleEvents::TERMINATE, $listener);
        }
    }
}
