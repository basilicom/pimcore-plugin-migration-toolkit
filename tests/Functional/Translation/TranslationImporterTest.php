<?php

declare(strict_types=1);

namespace Basilicom\PimcorePluginMigrationToolkit\Tests\Functional\Translation;

use Basilicom\PimcorePluginMigrationToolkit\Exceptions\InvalidSettingException;
use Basilicom\PimcorePluginMigrationToolkit\Tests\Functional\AbstractFunctionalTestCase;
use Basilicom\PimcorePluginMigrationToolkit\Translation\Overwrite;
use Basilicom\PimcorePluginMigrationToolkit\Translation\TranslationImporter;
use Pimcore\Model\Translation;

class TranslationImporterTest extends AbstractFunctionalTestCase
{
    private TranslationImporter $importer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importer = new TranslationImporter();
    }

    public function testCreatesKeysAndLabels(): void
    {
        $key = $this->translationKey();

        $result = $this->importer->import([$key => ['en' => 'Hello', 'de' => 'Hallo']], Translation::DOMAIN_DEFAULT, Overwrite::Never);

        self::assertSame(1, $result->createdKeys);
        self::assertSame(2, $result->addedLabels);
        self::assertSame(0, $result->replacedLabels);
        self::assertSame('Hallo', Translation::getByKey($key)?->getTranslation('de'));
    }

    public function testNeverKeepsExistingLabelsButFillsMissingOnes(): void
    {
        $key = $this->translationKey(['en' => 'Editor']);

        $result = $this->importer->import([$key => ['en' => 'Catalogue', 'de' => 'Ergänzt']], Translation::DOMAIN_DEFAULT, Overwrite::Never);

        self::assertSame(0, $result->createdKeys);
        self::assertSame(1, $result->addedLabels);
        self::assertSame('Editor', Translation::getByKey($key)?->getTranslation('en'));
        self::assertSame('Ergänzt', Translation::getByKey($key)?->getTranslation('de'));
    }

    public function testAlwaysReplacesExistingLabels(): void
    {
        $key = $this->translationKey(['en' => 'Editor']);

        $result = $this->importer->import([$key => ['en' => 'Catalogue']], Translation::DOMAIN_DEFAULT, Overwrite::Always);

        self::assertSame(1, $result->replacedLabels);
        self::assertSame('Catalogue', Translation::getByKey($key)?->getTranslation('en'));
    }

    public function testUnchangedLabelsAreNotCounted(): void
    {
        $key = $this->translationKey(['en' => 'Same']);

        $result = $this->importer->import([$key => ['en' => 'Same']], Translation::DOMAIN_DEFAULT, Overwrite::Always);

        self::assertSame(0, $result->replacedLabels + $result->addedLabels + $result->createdKeys);
    }

    public function testLocalesUnknownToTheDomainAreSkippedAndReported(): void
    {
        $key = $this->translationKey();

        $result = $this->importer->import([$key => ['xx' => 'Nope', 'en' => 'Yes']], Translation::DOMAIN_DEFAULT, Overwrite::Never);

        self::assertSame(['xx'], $result->skippedLocales);
        self::assertSame(['en' => 'Yes'], Translation::getByKey($key)?->getTranslations());
    }

    /**
     * The admin domain and its language list exist only with the classic admin UI bundle, which the
     * test rig does not install; the test follows whatever the installation provides.
     */
    public function testUsesTheLanguageListOfTheDomain(): void
    {
        if (!Translation::isAValidDomain(Translation::DOMAIN_ADMIN)) {
            $this->expectException(InvalidSettingException::class);
            $this->importer->import(['k' => ['en' => 'v']], Translation::DOMAIN_ADMIN, Overwrite::Never);

            return;
        }

        $key    = $this->translationKey(domain: Translation::DOMAIN_ADMIN);
        $result = $this->importer->import([$key => ['en' => 'Admin label']], Translation::DOMAIN_ADMIN, Overwrite::Never);

        if (in_array('en', Translation::getValidLanguages(Translation::DOMAIN_ADMIN), true)) {
            self::assertSame('Admin label', Translation::getByKey($key, Translation::DOMAIN_ADMIN)?->getTranslation('en'));
        } else {
            self::assertSame(['en'], $result->skippedLocales);
            self::assertNull(Translation::getByKey($key, Translation::DOMAIN_ADMIN));
        }
    }

    public function testUnknownDomainIsRejected(): void
    {
        $this->expectException(InvalidSettingException::class);

        $this->importer->import(['k' => ['en' => 'v']], 'nope' . bin2hex(random_bytes(3)), Overwrite::Never);
    }

    /** @param array<string, string> $seed */
    private function translationKey(array $seed = [], string $domain = Translation::DOMAIN_DEFAULT): string
    {
        $key = $this->uniqueName('toolkit.importer.');
        $this->onTearDown(static fn () => Translation::getByKey($key, $domain)?->delete());

        if ($seed !== []) {
            $translation = new Translation();
            $translation->setKey($key);
            $translation->setDomain($domain);
            foreach ($seed as $locale => $text) {
                $translation->addTranslation($locale, $text);
            }
            $translation->save();
        }

        return $key;
    }
}
