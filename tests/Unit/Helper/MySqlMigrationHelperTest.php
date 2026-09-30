<?php

declare(strict_types=1);

namespace Basilicom\PimcorePluginMigrationToolkit\Tests\Unit\Helper;

use Basilicom\PimcorePluginMigrationToolkit\Exceptions\NotFoundException;
use Basilicom\PimcorePluginMigrationToolkit\Helper\MySqlMigrationHelper;
use PHPUnit\Framework\TestCase;

class MySqlMigrationHelperTest extends TestCase
{
    private const string DATA_FOLDER = __DIR__ . '/../../Fixtures/Migration/data/FixtureMigration';

    public function testLoadsTheUpFile(): void
    {
        $sql = (new MySqlMigrationHelper(self::DATA_FOLDER))->loadSqlFile('create.sql');

        self::assertStringContainsString('CREATE TABLE toolkit_fixture', $sql);
    }

    public function testLoadsTheDownFileFromTheDownFolder(): void
    {
        $sql = (new MySqlMigrationHelper(self::DATA_FOLDER))->loadSqlFile('create.sql', MySqlMigrationHelper::DOWN);

        self::assertStringContainsString('DROP TABLE toolkit_fixture', $sql);
    }

    public function testMissingFileIsReported(): void
    {
        $this->expectException(NotFoundException::class);

        (new MySqlMigrationHelper(self::DATA_FOLDER))->loadSqlFile('missing.sql');
    }
}
