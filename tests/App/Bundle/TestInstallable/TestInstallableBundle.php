<?php

declare(strict_types=1);

namespace Basilicom\PimcorePluginMigrationToolkit\Tests\App\Bundle\TestInstallable;

use Pimcore\Extension\Bundle\AbstractPimcoreBundle;

/**
 * Smallest possible installable bundle, so BundleMigrationHelper can be exercised without
 * depending on one of Pimcore's own bundles.
 */
class TestInstallableBundle extends AbstractPimcoreBundle
{
    public function getInstaller(): Installer
    {
        return $this->container->get(Installer::class);
    }

    public function getPath(): string
    {
        return __DIR__;
    }
}
