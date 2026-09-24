<?php

declare(strict_types=1);

namespace Basilicom\PimcorePluginMigrationToolkit\Translation;

use Basilicom\PimcorePluginMigrationToolkit\Exceptions\InvalidSettingException;
use Pimcore\Model\Translation;

/**
 * The single write path into Pimcore's editable translations: the CSV import, the
 * catalogue sync and the migration helper all hand their key => [locale => text]
 * maps to this service. Locales the domain does not know are skipped and reported
 * instead of ending up as orphan rows.
 */
class TranslationImporter
{
    /**
     * @param iterable<string, array<string, string>> $translations key => [locale => text]
     *
     * @throws InvalidSettingException
     */
    public function import(iterable $translations, string $domain, Overwrite $overwrite): ImportResult
    {
        if (!Translation::isAValidDomain($domain)) {
            throw new InvalidSettingException(sprintf('"%s" is not a registered translation domain.', $domain));
        }

        $validLocales   = Translation::getValidLanguages($domain);
        $createdKeys    = 0;
        $addedLabels    = 0;
        $replacedLabels = 0;
        $skippedLocales = [];

        foreach ($translations as $key => $textsByLocale) {
            $translation = Translation::getByKey($key, $domain);
            $isNewKey    = $translation === null;

            if ($translation === null) {
                $translation = new Translation();
                $translation->setKey($key);
                $translation->setDomain($domain);
            }

            $changed = false;
            foreach ($textsByLocale as $locale => $text) {
                if (!in_array($locale, $validLocales, true)) {
                    $skippedLocales[$locale] = $locale;

                    continue;
                }

                $current    = $translation->getTranslation($locale);
                $hasCurrent = $current !== null && $current !== '';

                if ($hasCurrent && $overwrite === Overwrite::Never) {
                    continue;
                }

                if ($current === $text) {
                    continue;
                }

                $translation->addTranslation($locale, $text);
                $hasCurrent ? $replacedLabels++ : $addedLabels++;
                $changed = true;
            }

            if (!$changed) {
                continue;
            }

            $translation->save();

            if ($isNewKey) {
                $createdKeys++;
            }
        }

        return new ImportResult($domain, $createdKeys, $addedLabels, $replacedLabels, array_values($skippedLocales));
    }
}
