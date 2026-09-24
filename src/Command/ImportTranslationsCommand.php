<?php

declare(strict_types=1);

namespace Basilicom\PimcorePluginMigrationToolkit\Command;

use Basilicom\PimcorePluginMigrationToolkit\Exceptions\MigrationToolkitException;
use Basilicom\PimcorePluginMigrationToolkit\Translation\Overwrite;
use Basilicom\PimcorePluginMigrationToolkit\Translation\Reader\CsvTranslationReader;
use Basilicom\PimcorePluginMigrationToolkit\Translation\TranslationImporter;
use Pimcore\Console\AbstractCommand;
use Pimcore\Model\Translation;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: self::NAME,
    description: 'Imports a Pimcore translation CSV export (Tools > Translations) into the translations of one domain.',
)]
class ImportTranslationsCommand extends AbstractCommand
{
    public const string NAME = 'basilicom:translations:import';

    private const string ARGUMENT_FILE    = 'file';
    private const string OPTION_DOMAIN    = 'domain';
    private const string OPTION_OVERWRITE = 'overwrite';
    private const string OPTION_DELIMITER = 'delimiter';

    public function __construct(
        private readonly CsvTranslationReader $reader,
        private readonly TranslationImporter $importer,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument(self::ARGUMENT_FILE, InputArgument::REQUIRED, 'Path to the CSV file')
            ->addOption(self::OPTION_DOMAIN, 'd', InputOption::VALUE_REQUIRED, 'Translation domain', Translation::DOMAIN_DEFAULT)
            ->addOption(
                self::OPTION_OVERWRITE,
                'o',
                InputOption::VALUE_REQUIRED,
                sprintf('Overwrite existing labels: %s', implode('|', Overwrite::values())),
                Overwrite::Never->value,
            )
            ->addOption(self::OPTION_DELIMITER, null, InputOption::VALUE_REQUIRED, 'CSV delimiter; detected from the file when omitted');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $file      = (string) $input->getArgument(self::ARGUMENT_FILE);
        $domain    = (string) $input->getOption(self::OPTION_DOMAIN);
        $delimiter = $input->getOption(self::OPTION_DELIMITER);
        $overwrite = Overwrite::tryFrom((string) $input->getOption(self::OPTION_OVERWRITE));

        if ($overwrite === null) {
            $this->writeError(sprintf('Option --%s must be one of: %s', self::OPTION_OVERWRITE, implode(', ', Overwrite::values())));

            return self::INVALID;
        }

        try {
            $result = $this->importer->import(
                $this->reader->read($file, is_string($delimiter) ? $delimiter : null),
                $domain,
                $overwrite,
            );
        } catch (MigrationToolkitException $exception) {
            $this->writeError($exception->getMessage());

            return self::FAILURE;
        }

        $output->writeln(sprintf('<info>%s</info>', $result->summary()));

        return self::SUCCESS;
    }
}
