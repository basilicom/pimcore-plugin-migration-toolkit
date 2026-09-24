<?php

declare(strict_types=1);

namespace Basilicom\PimcorePluginMigrationToolkit\Tests\App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Fixture for MigrateInSeparateProcessesCommandTest: the test reverts and re-executes it in
 * child processes and checks for the table.
 */
final class Version20260101000000 extends AbstractMigration
{
    public const string TABLE = 'toolkit_fixture_migration';

    public function up(Schema $schema): void
    {
        $this->addSql(sprintf('CREATE TABLE %s (id INT NOT NULL)', self::TABLE));
    }

    public function down(Schema $schema): void
    {
        $this->addSql(sprintf('DROP TABLE %s', self::TABLE));
    }
}
