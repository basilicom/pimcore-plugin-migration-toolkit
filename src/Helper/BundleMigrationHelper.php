<?php

namespace Basilicom\PimcorePluginMigrationToolkit\Helper;

use Basilicom\PimcorePluginMigrationToolkit\Exceptions\InvalidSettingException;
use Pimcore;
use Pimcore\Extension\Bundle\PimcoreBundleManager;
use Pimcore\Tool\AssetsInstaller;

class BundleMigrationHelper extends AbstractMigrationHelper
{
    private PimcoreBundleManager $pimcoreBundleManager;
    private AssetsInstaller $assetsInstaller;

    public function __construct()
    {
        /** @var PimcoreBundleManager $bundleManager */
        $bundleManager = Pimcore::getKernel()->getContainer()->get(PimcoreBundleManager::class);
        $this->pimcoreBundleManager = $bundleManager;

        /** @var AssetsInstaller $assetsInstaller */
        $assetsInstaller = Pimcore::getKernel()->getContainer()->get(AssetsInstaller::class);
        $this->assetsInstaller = $assetsInstaller;
    }

    /**
     * @throws InvalidSettingException
     */
    public function enable(string $pluginId): void
    {
        $this->setState($pluginId, true);
    }

    /**
     * @throws InvalidSettingException
     */
    public function disable(string $pluginId): void
    {
        $this->setState($pluginId, false);
    }

    public function install(string $pluginId): void
    {
        $this->setInstallState($pluginId, true);
    }

    public function uninstall(string $pluginId): void
    {
        $this->setInstallState($pluginId, false);
    }

    /**
     * @throws InvalidSettingException
     */
    private function setState(string $pluginId, bool $enabled): void
    {
        $availableBundles = $this->pimcoreBundleManager->getAvailableBundles();
        $enabledBundleNames = $this->pimcoreBundleManager->getEnabledBundleNames();

        if (!in_array($pluginId, $availableBundles)) {
            $message = sprintf(
                'The bundle with the id "%s" does not exist.',
                $pluginId
            );

            throw new InvalidSettingException($message);
        }

        if ($enabled) {
            if (!in_array($pluginId, $enabledBundleNames)) {
                $this->pimcoreBundleManager->enable($pluginId);
            } else {
                $message = sprintf(
                    'The bundle with the id "%s" is already enabled.',
                    $pluginId
                );
                $this->getOutput()->writeMessage($message);
            }
        }

        if (!$enabled) {
            if (in_array($pluginId, $enabledBundleNames)) {
                $this->pimcoreBundleManager->disable($pluginId);
            } else {
                $message = sprintf(
                    'The bundle with the id "%s" is already disabled.',
                    $pluginId
                );
                $this->getOutput()->writeMessage($message);
            }
        }

        $this->assetsInstaller->install();

        $this->clearCache();
    }

    private function setInstallState(string $pluginId, bool $installed): void
    {
        $bundle = $this->pimcoreBundleManager->getActiveBundle($pluginId, false);

        if ($installed && $this->pimcoreBundleManager->canBeInstalled($bundle)) {
            $this->pimcoreBundleManager->install($bundle);
        } elseif (!$installed && $this->pimcoreBundleManager->canBeUninstalled($bundle)) {
            $this->pimcoreBundleManager->uninstall($bundle);
        }
    }
}
