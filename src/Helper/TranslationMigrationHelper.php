<?php

declare(strict_types=1);

namespace Basilicom\PimcorePluginMigrationToolkit\Helper;

use Basilicom\PimcorePluginMigrationToolkit\Translation\ImportResult;
use Basilicom\PimcorePluginMigrationToolkit\Translation\Overwrite;
use Basilicom\PimcorePluginMigrationToolkit\Translation\TranslationImporter;
use Pimcore\Model\Translation;

class TranslationMigrationHelper extends AbstractMigrationHelper
{
    public function __construct(private readonly TranslationImporter $importer)
    {
    }

    /** @param array<string, array<string, string>> $translations key => [locale => text] */
    public function addTranslations(
        array $translations,
        string $domain = Translation::DOMAIN_DEFAULT,
        Overwrite $overwrite = Overwrite::Always,
    ): ImportResult {
        $result = $this->importer->import($translations, $domain, $overwrite);
        $this->getOutput()->writeMessage($result->summary());

        return $result;
    }

    /** @param array<string> $keys */
    public function removeTranslationsByKey(array $keys, string $domain = Translation::DOMAIN_DEFAULT): void
    {
        foreach ($keys as $key) {
            Translation::getByKey($key, $domain)?->delete();
        }

        $this->forgetRuntimeCache();
    }
}
