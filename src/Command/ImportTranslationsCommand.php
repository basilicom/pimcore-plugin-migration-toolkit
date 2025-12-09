<?php

namespace Basilicom\PimcorePluginMigrationToolkit\Command;

use Basilicom\PimcorePluginMigrationToolkit\Translation\Exception\InvalidTranslationFileFormatException;
use Basilicom\PimcorePluginMigrationToolkit\Translation\TranslationService;
use Exception;
use Pimcore\Console\AbstractCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('basilicom:import:translations', 'imports shared translations from a csv file that are not present in the system yet')]
class ImportTranslationsCommand extends AbstractCommand
{
    private TranslationService $translationService;

    public function __construct()
    {
        parent::__construct();
        $this->translationService = new TranslationService;
    }

    protected function configure(): void
    {
        $this->setHelp('this command imports shared translations from a csv export. it will only import translations that are not in the system yet.')
            ->addArgument('path', InputArgument::REQUIRED, 'the path to the shared translations csv file')
            ->addOption('admin', null, InputOption::VALUE_NONE, 'import admin translations')
            ->addOption('delimiter', null, InputOption::VALUE_OPTIONAL, 'delimiter in the csv file', ';')
            ->addOption('replaceExistingTranslation', null, InputOption::VALUE_NONE, 'if the translation already exist, should it be replaced?');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @example bin/console basilicom:import-shared-translations /path/to/shared-translations.csv
     * @example bin/console basilicom:import-shared-translations /path/to/shared-translations.csv --delimiter=, --replaceExistingTranslation --admin
     *
     * the used file is probably an export from the pimcore admin interface. tools > translations > shared translations.
     * this command simply takes this file and imports it via pimcore api.
     * before, several validation checks will be made:
     *
     * - is the input file in expected format:
     *     does it have a column 'key'
     *     are there any fields that pimcore doesn't know about
     *
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('### Start Import of translation file.');

        $filePath = $input->getArgument('path');
        $isAdminTranslation = $input->getOption('admin');
        $delimiter = $input->getOption('delimiter');
        $replaceExistingTranslation = $input->getOption('replaceExistingTranslation');

        try {
            if ($isAdminTranslation) {
                $this->translationService->importAdminTranslationCsv($filePath, $delimiter, $replaceExistingTranslation);
            } else {
                $this->translationService->importSharedTranslationCsv($filePath, $delimiter, $replaceExistingTranslation);
            }
        } catch (InvalidTranslationFileFormatException $invalidTranslationFileFormatException) {
            $this->writeError('### Error: ' . $invalidTranslationFileFormatException->getMessage());
            return self::FAILURE;
        } catch (Exception $objException) {
            $this->writeError(sprintf('### Error: unable to import shared translations: %s', $objException->getMessage()));
            return self::FAILURE;
        }

        $output->writeln('### Finished Import of translation file.');

        return self::SUCCESS;
    }
}
