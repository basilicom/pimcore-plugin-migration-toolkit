<?php

declare(strict_types=1);

namespace Basilicom\PimcorePluginMigrationToolkit\Tests\Unit\Migration;

use Basilicom\PimcorePluginMigrationToolkit\Exceptions\NotFoundException;
use Basilicom\PimcorePluginMigrationToolkit\Helper\BundleMigrationHelper;
use Basilicom\PimcorePluginMigrationToolkit\Helper\ClassDefinitionMigrationHelper;
use Basilicom\PimcorePluginMigrationToolkit\Helper\CustomLayoutMigrationHelper;
use Basilicom\PimcorePluginMigrationToolkit\Helper\MySqlMigrationHelper;
use Basilicom\PimcorePluginMigrationToolkit\Helper\TranslationMigrationHelper;
use Basilicom\PimcorePluginMigrationToolkit\Helper\WebsiteSettingsMigrationHelper;
use Basilicom\PimcorePluginMigrationToolkit\Migration\MigrationHelperFactory;
use Basilicom\PimcorePluginMigrationToolkit\Translation\TranslationImporter;
use PHPUnit\Framework\TestCase;
use Pimcore\Extension\Bundle\PimcoreBundleManager;
use Pimcore\Tool\AssetsInstaller;
use Symfony\Component\Serializer\Encoder\JsonEncoder;

class MigrationHelperFactoryTest extends TestCase
{
    public function testEveryCallReturnsAFreshHelper(): void
    {
        $factory = new MigrationHelperFactory(new TranslationImporter(), new JsonEncoder());

        self::assertInstanceOf(WebsiteSettingsMigrationHelper::class, $factory->websiteSettings());
        self::assertNotSame($factory->websiteSettings(), $factory->websiteSettings());
        self::assertInstanceOf(TranslationMigrationHelper::class, $factory->translation());
    }

    public function testDataFolderBoundHelpersGetTheFolder(): void
    {
        $factory = new MigrationHelperFactory(new TranslationImporter(), new JsonEncoder());

        self::assertInstanceOf(ClassDefinitionMigrationHelper::class, $factory->classDefinition('/data'));
        self::assertSame('/data/class_X_export.json', $factory->classDefinition('/data')->getJsonDefinitionPathForUpMigration('X'));
        self::assertInstanceOf(CustomLayoutMigrationHelper::class, $factory->customLayout('/data'));
        self::assertInstanceOf(MySqlMigrationHelper::class, $factory->mySql('/data'));
    }

    public function testBundleHelperNeedsItsPimcoreCollaborators(): void
    {
        $withDependencies = new MigrationHelperFactory(
            new TranslationImporter(),
            new JsonEncoder(),
            $this->createMock(PimcoreBundleManager::class),
            $this->createMock(AssetsInstaller::class),
        );
        self::assertInstanceOf(BundleMigrationHelper::class, $withDependencies->bundle());

        $this->expectException(NotFoundException::class);
        (new MigrationHelperFactory(new TranslationImporter(), new JsonEncoder()))->bundle();
    }
}
