<?php

declare(strict_types=1);

namespace Basilicom\PimcorePluginMigrationToolkit\Command;

use Basilicom\PimcorePluginMigrationToolkit\DependencyInjection\Configuration;
use Basilicom\PimcorePluginMigrationToolkit\Exceptions\MigrationToolkitException;
use Basilicom\PimcorePluginMigrationToolkit\Translation\Overwrite;
use Basilicom\PimcorePluginMigrationToolkit\Translation\Reader\YamlCatalogueReader;
use Basilicom\PimcorePluginMigrationToolkit\Translation\TranslationImporter;
use Pimcore\Console\AbstractCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Brings the labels of the Symfony catalogues (`translations/<domain>.<locale>.yaml`) into
 * Pimcore's editable translations, so every key shows up under Tools > Translations after
 * a deploy. By default existing labels are kept: once a label is in the database, editors own it.
 */
#[AsCommand(
    name: self::NAME,
    description: 'Adds the labels of the Symfony YAML catalogues to Pimcore\'s editable translations.',
)]
class SyncTranslationsCommand extends AbstractCommand
{
    public const string NAME = 'basilicom:translations:sync';

    private const string OPTION_DOMAIN        = 'domain';
    private const string OPTION_CATALOGUE_DIR = 'catalogue-dir';
    private const string OPTION_OVERWRITE     = 'overwrite';

    /** @param array<string> $domains */
    public function __construct(
        private readonly YamlCatalogueReader $reader,
        private readonly TranslationImporter $importer,
        #[Autowire(param: Configuration::PARAMETER_CATALOGUE_DIR)]
        private readonly string $catalogueDir,
        #[Autowire(param: Configuration::PARAMETER_DOMAINS)]
        private readonly array $domains,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                self::OPTION_DOMAIN,
                'd',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                sprintf('Domain(s) to sync (default: %s)', implode(', ', $this->domains)),
            )
            ->addOption(
                self::OPTION_CATALOGUE_DIR,
                null,
                InputOption::VALUE_REQUIRED,
                'Directory holding the <domain>.<locale>.yaml catalogues',
                $this->catalogueDir,
            )
            ->addOption(
                self::OPTION_OVERWRITE,
                'o',
                InputOption::VALUE_REQUIRED,
                sprintf('Overwrite existing labels: %s', implode('|', Overwrite::values())),
                Overwrite::Never->value,
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var array<string> $domains */
        $domains      = $input->getOption(self::OPTION_DOMAIN) ?: $this->domains;
        $catalogueDir = (string) $input->getOption(self::OPTION_CATALOGUE_DIR);
        $overwrite    = Overwrite::tryFrom((string) $input->getOption(self::OPTION_OVERWRITE));

        if ($overwrite === null) {
            $this->writeError(sprintf('Option --%s must be one of: %s', self::OPTION_OVERWRITE, implode(', ', Overwrite::values())));

            return self::INVALID;
        }

        try {
            foreach ($domains as $domain) {
                $result = $this->importer->import($this->reader->read($catalogueDir, $domain), $domain, $overwrite);
                $output->writeln(sprintf('<info>%s</info>', $result->summary()));
            }
        } catch (MigrationToolkitException $exception) {
            $this->writeError($exception->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
