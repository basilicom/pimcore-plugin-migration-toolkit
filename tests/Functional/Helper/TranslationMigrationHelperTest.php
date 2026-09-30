<?php

declare(strict_types=1);

namespace Basilicom\PimcorePluginMigrationToolkit\Tests\Functional\Helper;

use Basilicom\PimcorePluginMigrationToolkit\Helper\TranslationMigrationHelper;
use Basilicom\PimcorePluginMigrationToolkit\Tests\Functional\AbstractFunctionalTestCase;
use Basilicom\PimcorePluginMigrationToolkit\Translation\Overwrite;
use Basilicom\PimcorePluginMigrationToolkit\Translation\TranslationImporter;
use Pimcore\Model\Translation;

class TranslationMigrationHelperTest extends AbstractFunctionalTestCase
{
    private TranslationMigrationHelper $helper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->helper = $this->withOutput(new TranslationMigrationHelper(new TranslationImporter()));
    }

    public function testAddTranslationsOverwritesByDefaultAndLogsTheResult(): void
    {
        $key = $this->key();
        $this->helper->addTranslations([$key => ['en' => 'First']]);

        $result = $this->helper->addTranslations([$key => ['en' => 'Second', 'de' => 'Zweite']]);

        self::assertSame(1, $result->replacedLabels);
        self::assertSame(1, $result->addedLabels);
        self::assertSame('Second', Translation::getByKey($key)?->getTranslation('en'));
        $this->assertMessageContains('[messages] created 0 key(s), added 1 label(s), replaced 1 label(s)');
    }

    public function testAddTranslationsCanKeepExistingLabels(): void
    {
        $key = $this->key();
        $this->helper->addTranslations([$key => ['en' => 'Editor']]);

        $this->helper->addTranslations([$key => ['en' => 'Catalogue']], Translation::DOMAIN_DEFAULT, Overwrite::Never);

        self::assertSame('Editor', Translation::getByKey($key)?->getTranslation('en'));
    }

    public function testRemoveTranslationsByKeyDeletesExistingAndIgnoresMissingKeys(): void
    {
        $key = $this->key();
        $this->helper->addTranslations([$key => ['en' => 'Gone soon']]);

        $this->helper->removeTranslationsByKey([$key, $this->uniqueName('toolkit.missing.')]);

        self::assertNull(Translation::getByKey($key));
    }

    private function key(): string
    {
        $key = $this->uniqueName('toolkit.helper.');
        $this->onTearDown(static fn () => Translation::getByKey($key)?->delete());

        return $key;
    }
}
