<?php

declare(strict_types=1);

namespace Basilicom\PimcorePluginMigrationToolkit\Migration;

use Basilicom\PimcorePluginMigrationToolkit\Exceptions\NotFoundException;
use Basilicom\PimcorePluginMigrationToolkit\Helper\AssetMigrationHelper;
use Basilicom\PimcorePluginMigrationToolkit\Helper\BundleMigrationHelper;
use Basilicom\PimcorePluginMigrationToolkit\Helper\ClassDefinitionMigrationHelper;
use Basilicom\PimcorePluginMigrationToolkit\Helper\ClassificationStoreMigrationHelper;
use Basilicom\PimcorePluginMigrationToolkit\Helper\CustomLayoutMigrationHelper;
use Basilicom\PimcorePluginMigrationToolkit\Helper\DataObjectMigrationHelper;
use Basilicom\PimcorePluginMigrationToolkit\Helper\DocumentMigrationHelper;
use Basilicom\PimcorePluginMigrationToolkit\Helper\FieldcollectionMigrationHelper;
use Basilicom\PimcorePluginMigrationToolkit\Helper\MySqlMigrationHelper;
use Basilicom\PimcorePluginMigrationToolkit\Helper\ObjectbrickMigrationHelper;
use Basilicom\PimcorePluginMigrationToolkit\Helper\QuantityValueUnitMigrationHelper;
use Basilicom\PimcorePluginMigrationToolkit\Helper\StaticRoutesMigrationHelper;
use Basilicom\PimcorePluginMigrationToolkit\Helper\TranslationMigrationHelper;
use Basilicom\PimcorePluginMigrationToolkit\Helper\UserMigrationHelper;
use Basilicom\PimcorePluginMigrationToolkit\Helper\UserRolesMigrationHelper;
use Basilicom\PimcorePluginMigrationToolkit\Helper\WebsiteSettingsMigrationHelper;
use Basilicom\PimcorePluginMigrationToolkit\Translation\TranslationImporter;
use Pimcore;
use Pimcore\Extension\Bundle\PimcoreBundleManager;
use Pimcore\Tool\AssetsInstaller;
use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;

/**
 * Builds the helpers a migration works with. It is a container service, so the helpers' collaborators
 * come from dependency injection; HelperAwareMigrationFactory hands it to every
 * AbstractAdvancedPimcoreMigration Doctrine instantiates. Each call returns a fresh helper, because
 * helpers carry per-migration state (output writer, data folder, publish flag).
 */
class MigrationHelperFactory
{
    public function __construct(
        private readonly TranslationImporter $translationImporter,
        private readonly DecoderInterface $jsonDecoder,
        private readonly ?PimcoreBundleManager $bundleManager = null,
        private readonly ?AssetsInstaller $assetsInstaller = null,
    ) {
    }

    /**
     * For migrations built outside the Doctrine factory, e.g. in unit tests: no injection, so the
     * bundle helper is only available when a Pimcore container is booted.
     */
    public static function standalone(): self
    {
        $container = Pimcore::hasContainer() ? Pimcore::getContainer() : null;

        $bundleManager   = $container?->get(PimcoreBundleManager::class);
        $assetsInstaller = $container?->get(AssetsInstaller::class);

        return new self(
            new TranslationImporter(),
            new JsonEncoder(),
            $bundleManager instanceof PimcoreBundleManager ? $bundleManager : null,
            $assetsInstaller instanceof AssetsInstaller ? $assetsInstaller : null,
        );
    }

    public function websiteSettings(): WebsiteSettingsMigrationHelper
    {
        return new WebsiteSettingsMigrationHelper();
    }

    public function staticRoutes(): StaticRoutesMigrationHelper
    {
        return new StaticRoutesMigrationHelper();
    }

    public function userRoles(): UserRolesMigrationHelper
    {
        return new UserRolesMigrationHelper();
    }

    public function user(): UserMigrationHelper
    {
        return new UserMigrationHelper();
    }

    /** @throws NotFoundException */
    public function bundle(): BundleMigrationHelper
    {
        if ($this->bundleManager === null || $this->assetsInstaller === null) {
            throw new NotFoundException('The bundle helper needs the PimcoreBundleManager and the AssetsInstaller, which are only available in a booted Pimcore.');
        }

        return new BundleMigrationHelper($this->bundleManager, $this->assetsInstaller);
    }

    public function classDefinition(string $dataFolder): ClassDefinitionMigrationHelper
    {
        return new ClassDefinitionMigrationHelper($dataFolder);
    }

    public function objectbrick(string $dataFolder): ObjectbrickMigrationHelper
    {
        return new ObjectbrickMigrationHelper($dataFolder);
    }

    public function fieldcollection(string $dataFolder): FieldcollectionMigrationHelper
    {
        return new FieldcollectionMigrationHelper($dataFolder);
    }

    public function customLayout(string $dataFolder): CustomLayoutMigrationHelper
    {
        return new CustomLayoutMigrationHelper($dataFolder, $this->jsonDecoder);
    }

    public function document(): DocumentMigrationHelper
    {
        return new DocumentMigrationHelper();
    }

    public function dataObject(): DataObjectMigrationHelper
    {
        return new DataObjectMigrationHelper();
    }

    public function asset(): AssetMigrationHelper
    {
        return new AssetMigrationHelper();
    }

    public function quantityValueUnit(): QuantityValueUnitMigrationHelper
    {
        return new QuantityValueUnitMigrationHelper();
    }

    public function mySql(string $dataFolder): MySqlMigrationHelper
    {
        return new MySqlMigrationHelper($dataFolder);
    }

    public function classificationStore(): ClassificationStoreMigrationHelper
    {
        return new ClassificationStoreMigrationHelper();
    }

    public function translation(): TranslationMigrationHelper
    {
        return new TranslationMigrationHelper($this->translationImporter);
    }
}
