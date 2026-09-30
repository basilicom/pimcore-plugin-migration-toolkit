<?php

declare(strict_types=1);

namespace Basilicom\PimcorePluginMigrationToolkit\Helper;

use Pimcore\Extension\Bundle\PimcoreBundleManager;
use Pimcore\Tool\AssetsInstaller;

class BundleMigrationHelper extends AbstractMigrationHelper
{
    private PimcoreBundleManager $pimcoreBundleManager;
    private AssetsInstaller $assetsInstaller;

    public function __construct(PimcoreBundleManager $bundleManager, AssetsInstaller $assetsInstaller)
    {
        $this->pimcoreBundleManager = $bundleManager;
        $this->assetsInstaller      = $assetsInstaller;
    }

    public function install(string $pluginId): void
    {
        $this->setInstallState($pluginId, true);
    }

    public function uninstall(string $pluginId): void
    {
        $this->setInstallState($pluginId, false);
    }

    private function setInstallState(string $pluginId, bool $installed): void
    {
        $bundle = $this->pimcoreBundleManager->getActiveBundle($pluginId, false);

        if ($installed && $this->pimcoreBundleManager->canBeInstalled($bundle)) {
            $this->pimcoreBundleManager->install($bundle);
        } elseif (!$installed && $this->pimcoreBundleManager->canBeUninstalled($bundle)) {
            $this->pimcoreBundleManager->uninstall($bundle);
        }

        $this->assetsInstaller->install();
    }
}
