<?php

declare(strict_types=1);

namespace Basilicom\PimcorePluginMigrationToolkit\Tests\Functional\Helper;

use Basilicom\PimcorePluginMigrationToolkit\Helper\BundleMigrationHelper;
use Basilicom\PimcorePluginMigrationToolkit\Tests\App\Bundle\TestInstallable\TestInstallableBundle;
use Basilicom\PimcorePluginMigrationToolkit\Tests\Functional\AbstractFunctionalTestCase;
use Pimcore;
use Pimcore\Extension\Bundle\PimcoreBundleManager;
use Pimcore\Tool\AssetsInstaller;

class BundleMigrationHelperTest extends AbstractFunctionalTestCase
{
    private BundleMigrationHelper $helper;
    private PimcoreBundleManager $bundleManager;

    protected function setUp(): void
    {
        parent::setUp();

        $bundleManager   = Pimcore::getContainer()?->get(PimcoreBundleManager::class);
        $assetsInstaller = Pimcore::getContainer()?->get(AssetsInstaller::class);
        self::assertInstanceOf(PimcoreBundleManager::class, $bundleManager);
        self::assertInstanceOf(AssetsInstaller::class, $assetsInstaller);

        $this->bundleManager = $bundleManager;
        $this->helper        = $this->withOutput(new BundleMigrationHelper($bundleManager, $assetsInstaller));
        $this->onTearDown(fn () => $this->helper->uninstall(TestInstallableBundle::class));
    }

    public function testInstallAndUninstall(): void
    {
        self::assertFalse($this->isInstalled());

        $this->helper->install(TestInstallableBundle::class);
        self::assertTrue($this->isInstalled());

        $this->helper->install(TestInstallableBundle::class);
        self::assertTrue($this->isInstalled(), 'installing twice is a no-op');

        $this->helper->uninstall(TestInstallableBundle::class);
        self::assertFalse($this->isInstalled());

        $this->helper->uninstall(TestInstallableBundle::class);
        self::assertFalse($this->isInstalled(), 'uninstalling twice is a no-op');
    }

    private function isInstalled(): bool
    {
        return $this->bundleManager->isInstalled($this->bundleManager->getActiveBundle(TestInstallableBundle::class, false));
    }
}
