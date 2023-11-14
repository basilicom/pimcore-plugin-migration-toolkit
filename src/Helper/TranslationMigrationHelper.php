<?php

namespace Basilicom\PimcorePluginMigrationToolkit\Helper;

use Exception;
use Pimcore\Model\Translation;

class TranslationMigrationHelper extends AbstractMigrationHelper
{
    /**
     * @throws Exception
     */
    public function addTranslations(array $translations, string $domain = 'messages'): void
    {
        foreach ($translations as $key => $languages) {
            $trans = Translation::getByKey($key, $domain);
            if (empty($trans)) {
                $trans = new Translation();
                $trans->setKey($key);
                $trans->setDomain($domain);
            }
            foreach ($languages as $language => $translation) {
                $trans->addTranslation($language, $translation);
            }
            $trans->save();
        }
    }

    /**
     * @throws Exception
     */
    public function removeTranslationsByKey(array $keys, string $domain = 'messages'): void
    {
        foreach ($keys as $key) {
            $trans = Translation::getByKey($key, $domain);
            $trans?->delete();
        }
    }
}
