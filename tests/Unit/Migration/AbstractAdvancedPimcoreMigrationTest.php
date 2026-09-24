<?php

declare(strict_types=1);

namespace Basilicom\PimcorePluginMigrationToolkit\Tests\Unit\Migration;

use Basilicom\PimcorePluginMigrationToolkit\Helper\AssetMigrationHelper;
use Basilicom\PimcorePluginMigrationToolkit\Helper\BundleMigrationHelper;
use Basilicom\PimcorePluginMigrationToolkit\Helper\ClassDefinitionMigrationHelper;
use Basilicom\PimcorePluginMigrationToolkit\Helper\ClassificationStoreMigrationHelper;
use Basilicom\PimcorePluginMigrationToolkit\Helper\CustomLayoutMigrationHelper;
use Basilicom\PimcorePluginMigrationToolkit\Helper\DataObjectMigrationHelper;
use Basilicom\PimcorePluginMigrationToolkit\Helper\DocumentMigrationHelper;
use Basilicom\PimcorePluginMigrationToolkit\Helper\FieldcollectionMigrationHelper;
use Basilicom\PimcorePluginMigrationToolkit\Helper\MySqlMigrationHelper;
use Basilicom\PimcorePluginMigrationToolkit\Helper\ObjectbrickMigrationHelper;
use Basilicom\PimcorePluginMigrationToolkit\Helper\QuantityValueUnitMigrationHelper;
use Basilicom\PimcorePluginMigrationToolkit\Helper\StaticRoutesMigrationHelper;
use Basilicom\PimcorePluginMigrationToolkit\Helper\TranslationMigrationHelper;
use Basilicom\PimcorePluginMigrationToolkit\Helper\UserMigrationHelper;
use Basilicom\PimcorePluginMigrationToolkit\Helper\UserRolesMigrationHelper;
use Basilicom\PimcorePluginMigrationToolkit\Helper\WebsiteSettingsMigrationHelper;
use Basilicom\PimcorePluginMigrationToolkit\Migration\MigrationHelperFactory;
use Basilicom\PimcorePluginMigrationToolkit\OutputWriter\CallbackOutputWriter;
use Basilicom\PimcorePluginMigrationToolkit\Tests\Fixtures\Migration\FixtureMigration;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class AbstractAdvancedPimcoreMigrationTest extends TestCase
{
    private FixtureMigration $migration;

    protected function setUp(): void
    {
        $this->migration = new FixtureMigration($this->createMock(Connection::class), new NullLogger());
    }

    public function testDataFolderSitsNextToTheMigrationClass(): void
    {
        self::assertSame(
            realpath(__DIR__ . '/../../Fixtures/Migration') . '/data/FixtureMigration',
            $this->migration->getDataFolder(),
        );
    }

    public function testOutputWriterIsACallbackWriter(): void
    {
        self::assertInstanceOf(CallbackOutputWriter::class, $this->migration->getOutputWriter());
    }

    public function testHelpersAreCreatedOnceAndTyped(): void
    {
        $expectations = [
            'getWebsiteSettingsMigrationHelper'     => WebsiteSettingsMigrationHelper::class,
            'getStaticRoutesMigrationHelper'        => StaticRoutesMigrationHelper::class,
            'getUserRolesMigrationHelper'           => UserRolesMigrationHelper::class,
            'getUserMigrationHelper'                => UserMigrationHelper::class,
            'getBundleMigrationHelper'              => BundleMigrationHelper::class,
            'getClassDefinitionMigrationHelper'     => ClassDefinitionMigrationHelper::class,
            'getObjectBrickMigrationHelper'         => ObjectbrickMigrationHelper::class,
            'getFieldCollectionMigrationHelper'     => FieldcollectionMigrationHelper::class,
            'getCustomLayoutMigrationHelper'        => CustomLayoutMigrationHelper::class,
            'getDocumentMigrationHelper'            => DocumentMigrationHelper::class,
            'getDataObjectMigrationHelper'          => DataObjectMigrationHelper::class,
            'getAssetMigrationHelper'               => AssetMigrationHelper::class,
            'getQuantityValueUnitMigrationHelper'   => QuantityValueUnitMigrationHelper::class,
            'getMySqlMigrationHelper'               => MySqlMigrationHelper::class,
            'getClassificationStoreMigrationHelper' => ClassificationStoreMigrationHelper::class,
            'getTranslationMigrationHelper'         => TranslationMigrationHelper::class,
        ];

        foreach ($expectations as $getter => $class) {
            $helper = $this->migration->{$getter}();

            self::assertInstanceOf($class, $helper, $getter);
            self::assertSame($helper, $this->migration->{$getter}(), $getter . ' is cached');
        }
    }

    public function testFallsBackToAStandaloneHelperFactory(): void
    {
        self::assertInstanceOf(MigrationHelperFactory::class, $this->migration->helperFactory());
        self::assertSame($this->migration->helperFactory(), $this->migration->helperFactory());
    }

    public function testDataFolderFeedsTheFileBasedHelpers(): void
    {
        $sql = $this->migration->getMySqlMigrationHelper()->loadSqlFile('create.sql');

        self::assertStringContainsString('toolkit_fixture', $sql);
        self::assertStringStartsWith(
            $this->migration->getDataFolder(),
            $this->migration->getClassDefinitionMigrationHelper()->getJsonDefinitionPathForUpMigration('Any'),
        );
    }
}
