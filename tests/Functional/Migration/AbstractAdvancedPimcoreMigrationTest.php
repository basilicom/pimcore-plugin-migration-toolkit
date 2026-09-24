<?php

declare(strict_types=1);

namespace Basilicom\PimcorePluginMigrationToolkit\Tests\Functional\Migration;

use Basilicom\PimcorePluginMigrationToolkit\Helper\BundleMigrationHelper;
use Basilicom\PimcorePluginMigrationToolkit\Tests\Fixtures\Migration\FixtureMigration;
use Basilicom\PimcorePluginMigrationToolkit\Tests\Functional\AbstractFunctionalTestCase;
use Pimcore\Db;
use Pimcore\Model\Translation;
use Psr\Log\NullLogger;

class AbstractAdvancedPimcoreMigrationTest extends AbstractFunctionalTestCase
{
    public function testBundleHelperIsWiredFromTheContainer(): void
    {
        $migration = new FixtureMigration(Db::get(), new NullLogger());

        $helper = $migration->getBundleMigrationHelper();

        self::assertInstanceOf(BundleMigrationHelper::class, $helper);
        self::assertSame($helper, $migration->getBundleMigrationHelper());
    }

    public function testHelpersWriteThroughTheMigrationOutput(): void
    {
        $migration = new FixtureMigration(Db::get(), new NullLogger());
        $key       = $this->uniqueName('toolkit.migration.');
        $this->onTearDown(static fn () => Translation::getByKey($key)?->delete());

        $result = $migration->getTranslationMigrationHelper()->addTranslations([$key => ['en' => 'From migration']]);

        self::assertSame(1, $result->createdKeys);
        self::assertSame('From migration', Translation::getByKey($key)?->getTranslation('en'));
    }
}
