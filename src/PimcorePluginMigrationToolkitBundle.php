<?php

declare(strict_types=1);

namespace Basilicom\PimcorePluginMigrationToolkit;

use Composer\InstalledVersions;
use Pimcore\Extension\Bundle\AbstractPimcoreBundle;

class PimcorePluginMigrationToolkitBundle extends AbstractPimcoreBundle
{
    /** @inheritDoc */
    public function getDescription(): string
    {
        return 'Set of Migration Helpers and further Migration Tools for Pimcore Migrations.';
    }

    /** @inheritDoc */
    public function getVersion(): string
    {
        return InstalledVersions::getVersion('basilicom/pimcore-plugin-migration-toolkit');
    }
}
