<?php

declare(strict_types=1);

namespace Basilicom\PimcorePluginMigrationToolkit\Tests\Functional\Helper;

use Basilicom\PimcorePluginMigrationToolkit\Exceptions\InvalidSettingException;
use Basilicom\PimcorePluginMigrationToolkit\Helper\WebsiteSettingsMigrationHelper;
use Basilicom\PimcorePluginMigrationToolkit\Tests\Functional\AbstractFunctionalTestCase;
use Pimcore\Model\WebsiteSetting;

class WebsiteSettingsMigrationHelperTest extends AbstractFunctionalTestCase
{
    private const int ROOT_ID    = 1;
    private const int MISSING_ID = 987654321;

    private WebsiteSettingsMigrationHelper $helper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->helper = $this->withOutput(new WebsiteSettingsMigrationHelper());
    }

    public function testCreateOfTypeText(): void
    {
        $name = $this->settingName();

        $this->helper->createOfTypeText($name, 'hello', 'de');

        $setting = WebsiteSetting::getByName($name, null, 'de');
        self::assertSame(WebsiteSettingsMigrationHelper::TYPE_TEXT, $setting?->getType());
        self::assertSame('hello', $setting?->getData());
        self::assertSame('de', $setting?->getLanguage());
    }

    public function testCreateOfTypeDocument(): void
    {
        $name = $this->settingName();

        $this->helper->createOfTypeDocument($name, self::ROOT_ID);

        $setting = WebsiteSetting::getByName($name);
        self::assertSame(WebsiteSettingsMigrationHelper::TYPE_DOCUMENT, $setting?->getType());
        self::assertSame(self::ROOT_ID, (int) $setting?->getData());
    }

    public function testCreateOfTypeDocumentRejectsUnknownDocuments(): void
    {
        $this->expectException(InvalidSettingException::class);

        $this->helper->createOfTypeDocument($this->settingName(), self::MISSING_ID);
    }

    public function testCreateOfTypeAsset(): void
    {
        $name = $this->settingName();

        $this->helper->createOfTypeAsset($name, self::ROOT_ID);

        self::assertSame(WebsiteSettingsMigrationHelper::TYPE_ASSET, WebsiteSetting::getByName($name)?->getType());
    }

    public function testCreateOfTypeAssetRejectsUnknownAssets(): void
    {
        $this->expectException(InvalidSettingException::class);

        $this->helper->createOfTypeAsset($this->settingName(), self::MISSING_ID);
    }

    public function testCreateOfTypeObject(): void
    {
        $name = $this->settingName();

        $this->helper->createOfTypeObject($name, self::ROOT_ID);

        self::assertSame(WebsiteSettingsMigrationHelper::TYPE_OBJECT, WebsiteSetting::getByName($name)?->getType());
    }

    public function testCreateOfTypeObjectRejectsUnknownObjects(): void
    {
        $this->expectException(InvalidSettingException::class);

        $this->helper->createOfTypeObject($this->settingName(), self::MISSING_ID);
    }

    public function testCreateOfTypeBool(): void
    {
        $name = $this->settingName();

        $this->helper->createOfTypeBool($name, true);

        $setting = WebsiteSetting::getByName($name);
        self::assertSame(WebsiteSettingsMigrationHelper::TYPE_BOOL, $setting?->getType());
        self::assertTrue((bool) $setting?->getData());
    }

    public function testExistingSettingIsKeptAndReported(): void
    {
        $name = $this->settingName();
        $this->helper->createOfTypeText($name, 'first');

        $this->helper->createOfTypeText($name, 'second');

        self::assertSame('first', WebsiteSetting::getByName($name)?->getData());
        $this->assertMessageContains('Setting with this name already exists');
    }

    public function testUnknownLanguageIsRejected(): void
    {
        $this->expectException(InvalidSettingException::class);

        $this->helper->createOfTypeText($this->settingName(), 'text', 'xx');
    }

    public function testUnknownSiteIsRejected(): void
    {
        $this->expectException(InvalidSettingException::class);

        $this->helper->createOfTypeText($this->settingName(), 'text', null, self::MISSING_ID);
    }

    public function testDelete(): void
    {
        $name = $this->settingName();
        $this->helper->createOfTypeText($name, 'gone');

        $this->helper->delete($name);

        self::assertNull(WebsiteSetting::getByName($name));
    }

    public function testDeleteOfMissingSettingIsReported(): void
    {
        $this->helper->delete($this->uniqueName('toolkitMissing'));

        $this->assertMessageContains('does not exist');
    }

    private function settingName(): string
    {
        $name = $this->uniqueName('toolkitSetting');
        $this->onTearDown(static function () use ($name): void {
            WebsiteSetting::getByName($name)?->delete();
            WebsiteSetting::getByName($name, null, 'de')?->delete();
        });

        return $name;
    }
}
