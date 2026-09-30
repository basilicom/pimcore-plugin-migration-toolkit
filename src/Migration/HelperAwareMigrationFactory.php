<?php

declare(strict_types=1);

namespace Basilicom\PimcorePluginMigrationToolkit\Migration;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Version\MigrationFactory;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;

/**
 * Decorates Doctrine's migration factory the same way the DoctrineMigrationsBundle does for
 * container-aware migrations: every toolkit migration gets the helper factory from the container.
 */
#[AsDecorator('doctrine.migrations.migrations_factory')]
class HelperAwareMigrationFactory implements MigrationFactory
{
    public function __construct(
        #[AutowireDecorated]
        private readonly MigrationFactory $inner,
        private readonly MigrationHelperFactory $helperFactory,
    ) {
    }

    public function createVersion(string $migrationClassName): AbstractMigration
    {
        $migration = $this->inner->createVersion($migrationClassName);

        if ($migration instanceof AbstractAdvancedPimcoreMigration) {
            $migration->setMigrationHelperFactory($this->helperFactory);
        }

        return $migration;
    }
}
