<?php

declare(strict_types=1);

namespace Basilicom\PimcorePluginMigrationToolkit\Tests\Fixtures\Migration;

use Basilicom\PimcorePluginMigrationToolkit\Migration\AbstractAdvancedPimcoreMigration;
use Basilicom\PimcorePluginMigrationToolkit\Migration\MigrationHelperFactory;
use Doctrine\DBAL\Schema\Schema;

/**
 * Minimal migration used to exercise the base class: its data folder is
 * tests/Fixtures/Migration/data/FixtureMigration.
 */
class FixtureMigration extends AbstractAdvancedPimcoreMigration
{
    public function up(Schema $schema): void
    {
    }

    public function down(Schema $schema): void
    {
    }

    public function helperFactory(): MigrationHelperFactory
    {
        return $this->getMigrationHelperFactory();
    }
}
