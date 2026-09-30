<?php

declare(strict_types=1);

namespace Basilicom\PimcorePluginMigrationToolkit\Translation\Reader;

use Basilicom\PimcorePluginMigrationToolkit\Translation\Exception\InvalidTranslationFileException;
use Symfony\Component\Translation\Loader\YamlFileLoader;

/**
 * Reads the Symfony catalogues `<domain>.<locale>.yaml` of one domain from a directory.
 * Symfony's loader flattens nested keys into the dotted notation Pimcore stores.
 */
class YamlCatalogueReader
{
    private const array EXTENSIONS = ['yaml', 'yml'];

    /**
     * @return array<string, array<string, string>> key => [locale => text]
     *
     * @throws InvalidTranslationFileException
     */
    public function read(string $directory, string $domain): array
    {
        if (!is_dir($directory)) {
            throw new InvalidTranslationFileException(sprintf('Catalogue directory "%s" does not exist.', $directory));
        }

        $loader    = new YamlFileLoader();
        $catalogue = [];

        foreach ($this->catalogueFiles($directory, $domain) as $locale => $file) {
            foreach ($loader->load($file, $locale, $domain)->all($domain) as $key => $text) {
                $catalogue[(string) $key][$locale] = (string) $text;
            }
        }

        return $catalogue;
    }

    /** @return array<string, string> locale => file */
    private function catalogueFiles(string $directory, string $domain): array
    {
        $files = [];
        foreach (self::EXTENSIONS as $extension) {
            foreach (glob(sprintf('%s/%s.*.%s', $directory, $domain, $extension)) ?: [] as $file) {
                $parts = explode('.', basename($file));
                if (count($parts) !== 3) {
                    continue;
                }

                $files[$parts[1]] = $file;
            }
        }

        ksort($files);

        return $files;
    }
}
