<?php

declare(strict_types=1);

namespace Basilicom\PimcorePluginMigrationToolkit\Migration;

use Basilicom\PimcorePluginMigrationToolkit\Exceptions\NotFoundException;
use Basilicom\PimcorePluginMigrationToolkit\Helper\AbstractMigrationHelper;
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
use Basilicom\PimcorePluginMigrationToolkit\OutputWriter\CallbackOutputWriter;
use Doctrine\DBAL\Connection;
use Doctrine\Migrations\AbstractMigration;
use Psr\Log\LoggerInterface;
use ReflectionClass;

/**
 * Base class for project migrations. The helpers come from the MigrationHelperFactory that
 * HelperAwareMigrationFactory injects; a migration constructed by hand falls back to a standalone
 * factory. Every helper is created once per migration and writes through the migration's output.
 */
abstract class AbstractAdvancedPimcoreMigration extends AbstractMigration
{
    private ?MigrationHelperFactory $helperFactory = null;

    /** @var array<class-string<AbstractMigrationHelper>, AbstractMigrationHelper> */
    private array $helpers = [];

    private string $dataFolder;

    public function __construct(Connection $connection, LoggerInterface $logger)
    {
        parent::__construct($connection, $logger);

        $reflection       = new ReflectionClass($this);
        $this->dataFolder = dirname((string) $reflection->getFileName()) . '/data/' . $reflection->getShortName();
    }

    public function setMigrationHelperFactory(MigrationHelperFactory $helperFactory): void
    {
        $this->helperFactory = $helperFactory;
    }

    public function getDataFolder(): string
    {
        return $this->dataFolder;
    }

    public function getOutputWriter(): CallbackOutputWriter
    {
        return new CallbackOutputWriter(function (string $message): void {
            $this->write($message);
        });
    }

    public function getWebsiteSettingsMigrationHelper(): WebsiteSettingsMigrationHelper
    {
        return $this->helper(WebsiteSettingsMigrationHelper::class, static fn (MigrationHelperFactory $factory) => $factory->websiteSettings());
    }

    public function getStaticRoutesMigrationHelper(): StaticRoutesMigrationHelper
    {
        return $this->helper(StaticRoutesMigrationHelper::class, static fn (MigrationHelperFactory $factory) => $factory->staticRoutes());
    }

    public function getUserRolesMigrationHelper(): UserRolesMigrationHelper
    {
        return $this->helper(UserRolesMigrationHelper::class, static fn (MigrationHelperFactory $factory) => $factory->userRoles());
    }

    public function getUserMigrationHelper(): UserMigrationHelper
    {
        return $this->helper(UserMigrationHelper::class, static fn (MigrationHelperFactory $factory) => $factory->user());
    }

    /** @throws NotFoundException */
    public function getBundleMigrationHelper(): BundleMigrationHelper
    {
        return $this->helper(BundleMigrationHelper::class, static fn (MigrationHelperFactory $factory) => $factory->bundle());
    }

    public function getClassDefinitionMigrationHelper(): ClassDefinitionMigrationHelper
    {
        return $this->helper(ClassDefinitionMigrationHelper::class, fn (MigrationHelperFactory $factory) => $factory->classDefinition($this->dataFolder));
    }

    public function getObjectBrickMigrationHelper(): ObjectbrickMigrationHelper
    {
        return $this->helper(ObjectbrickMigrationHelper::class, fn (MigrationHelperFactory $factory) => $factory->objectbrick($this->dataFolder));
    }

    public function getFieldCollectionMigrationHelper(): FieldcollectionMigrationHelper
    {
        return $this->helper(FieldcollectionMigrationHelper::class, fn (MigrationHelperFactory $factory) => $factory->fieldcollection($this->dataFolder));
    }

    public function getCustomLayoutMigrationHelper(): CustomLayoutMigrationHelper
    {
        return $this->helper(CustomLayoutMigrationHelper::class, fn (MigrationHelperFactory $factory) => $factory->customLayout($this->dataFolder));
    }

    public function getDocumentMigrationHelper(): DocumentMigrationHelper
    {
        return $this->helper(DocumentMigrationHelper::class, static fn (MigrationHelperFactory $factory) => $factory->document());
    }

    public function getDataObjectMigrationHelper(): DataObjectMigrationHelper
    {
        return $this->helper(DataObjectMigrationHelper::class, static fn (MigrationHelperFactory $factory) => $factory->dataObject());
    }

    public function getAssetMigrationHelper(): AssetMigrationHelper
    {
        return $this->helper(AssetMigrationHelper::class, static fn (MigrationHelperFactory $factory) => $factory->asset());
    }

    public function getQuantityValueUnitMigrationHelper(): QuantityValueUnitMigrationHelper
    {
        return $this->helper(QuantityValueUnitMigrationHelper::class, static fn (MigrationHelperFactory $factory) => $factory->quantityValueUnit());
    }

    public function getMySqlMigrationHelper(): MySqlMigrationHelper
    {
        return $this->helper(MySqlMigrationHelper::class, fn (MigrationHelperFactory $factory) => $factory->mySql($this->dataFolder));
    }

    public function getClassificationStoreMigrationHelper(): ClassificationStoreMigrationHelper
    {
        return $this->helper(ClassificationStoreMigrationHelper::class, static fn (MigrationHelperFactory $factory) => $factory->classificationStore());
    }

    public function getTranslationMigrationHelper(): TranslationMigrationHelper
    {
        return $this->helper(TranslationMigrationHelper::class, static fn (MigrationHelperFactory $factory) => $factory->translation());
    }

    protected function getMigrationHelperFactory(): MigrationHelperFactory
    {
        return $this->helperFactory ??= MigrationHelperFactory::standalone();
    }

    /**
     * @template T of AbstractMigrationHelper
     *
     * @param class-string<T> $class
     * @param callable(MigrationHelperFactory): T $create
     *
     * @return T
     */
    private function helper(string $class, callable $create): AbstractMigrationHelper
    {
        if (!isset($this->helpers[$class])) {
            $helper = $create($this->getMigrationHelperFactory());
            $helper->setOutput($this->getOutputWriter());
            $this->helpers[$class] = $helper;
        }

        /** @var T */
        return $this->helpers[$class];
    }
}
