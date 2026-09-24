<?php

declare(strict_types=1);

namespace Basilicom\PimcorePluginMigrationToolkit\Translation\Reader;

use Basilicom\PimcorePluginMigrationToolkit\Translation\Exception\InvalidTranslationFileException;
use Pimcore\Model\Element\Service as ElementService;
use Pimcore\Tool\Admin;

/**
 * Reads the CSV format Pimcore exports under Tools > Translations: a `key` column plus
 * one column per locale. The dialect is detected the way Pimcore's own import does it
 * unless a delimiter is given.
 */
class CsvTranslationReader
{
    private const string COLUMN_KEY  = 'key';
    private const array META_COLUMNS = ['creationDate', 'modificationDate'];
    private const string UTF8_BOM    = "\xEF\xBB\xBF";

    /**
     * @return iterable<string, array<string, string>> key => [locale => text]
     *
     * @throws InvalidTranslationFileException
     */
    public function read(string $file, ?string $delimiter = null): iterable
    {
        if (!is_file($file) || !is_readable($file)) {
            throw new InvalidTranslationFileException(sprintf('File "%s" does not exist or is not readable.', $file));
        }

        $dialect = Admin::determineCsvDialect($file);
        if ($delimiter !== null && $delimiter !== '') {
            $dialect->delimiter = $delimiter;
        }

        $handle = fopen($file, 'r');
        if ($handle === false) {
            throw new InvalidTranslationFileException(sprintf('File "%s" could not be opened.', $file));
        }

        try {
            $header = fgetcsv($handle, 0, $dialect->delimiter, $dialect->quotechar, $dialect->escapechar);
            if (!is_array($header)) {
                throw new InvalidTranslationFileException(sprintf('File "%s" has no header row.', $file));
            }

            $columns  = array_map(fn (?string $column): string => trim(str_replace(self::UTF8_BOM, '', (string) $column), '"'), $header);
            $keyIndex = array_search(self::COLUMN_KEY, $columns, true);
            if ($keyIndex === false) {
                throw new InvalidTranslationFileException(sprintf('Required column "%s" is missing.', self::COLUMN_KEY));
            }

            $localeColumns = array_filter(
                $columns,
                fn (string $column, int $index): bool => $index !== $keyIndex && $column !== '' && !in_array($column, self::META_COLUMNS, true),
                ARRAY_FILTER_USE_BOTH,
            );

            while (($row = fgetcsv($handle, 0, $dialect->delimiter, $dialect->quotechar, $dialect->escapechar)) !== false) {
                $row = ElementService::unEscapeCsvRecord($row);
                $key = trim((string) ($row[$keyIndex] ?? ''));
                if ($key === '') {
                    continue;
                }

                $textsByLocale = [];
                foreach ($localeColumns as $index => $locale) {
                    $text = str_replace('&quot;', '"', (string) ($row[$index] ?? ''));
                    if ($text !== '') {
                        $textsByLocale[$locale] = $text;
                    }
                }

                yield $key => $textsByLocale;
            }
        } finally {
            fclose($handle);
        }
    }
}
