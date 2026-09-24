<?php

declare(strict_types=1);

namespace Basilicom\PimcorePluginMigrationToolkit\Tests\Unit\Migration;

use Basilicom\PimcorePluginMigrationToolkit\Helper\WebsiteSettingsMigrationHelper;
use Basilicom\PimcorePluginMigrationToolkit\Migration\HelperAwareMigrationFactory;
use Basilicom\PimcorePluginMigrationToolkit\Migration\MigrationHelperFactory;
use Basilicom\PimcorePluginMigrationToolkit\Tests\Fixtures\Migration\FixtureMigration;
use Doctrine\DBAL\Connection;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Version\MigrationFactory;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class HelperAwareMigrationFactoryTest extends TestCase
{
    public function testToolkitMigrationsGetTheHelperFactory(): void
    {
        $migration     = new FixtureMigration($this->createMock(Connection::class), new NullLogger());
        $helperFactory = $this->createMock(MigrationHelperFactory::class);
        $helper        = new WebsiteSettingsMigrationHelper();
        $helperFactory->expects(self::once())->method('websiteSettings')->willReturn($helper);

        $migration = (new HelperAwareMigrationFactory($this->innerReturning($migration), $helperFactory))
            ->createVersion(FixtureMigration::class);

        self::assertInstanceOf(FixtureMigration::class, $migration);
        self::assertSame($helperFactory, $migration->helperFactory());
        self::assertSame($helper, $migration->getWebsiteSettingsMigrationHelper());
    }

    public function testOtherMigrationsPassThroughUntouched(): void
    {
        $plain = $this->createMock(AbstractMigration::class);

        $result = (new HelperAwareMigrationFactory($this->innerReturning($plain), $this->createMock(MigrationHelperFactory::class)))
            ->createVersion($plain::class);

        self::assertSame($plain, $result);
    }

    private function innerReturning(AbstractMigration $migration): MigrationFactory
    {
        $inner = $this->createMock(MigrationFactory::class);
        $inner->method('createVersion')->willReturn($migration);

        return $inner;
    }
}
