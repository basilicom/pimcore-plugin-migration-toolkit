<?php

namespace PimcorePluginMigrationToolkit;

use PimcorePluginMigrationToolkit\Helper\LanguageSettingsMigrationHelper;
use PimcorePluginMigrationToolkit\Helper\StaticRoutesMigrationHelper;
use PimcorePluginMigrationToolkit\Helper\SystemSettingsMigrationHelper;
use PimcorePluginMigrationToolkit\Helper\UserRolesMigrationHelper;
use PimcorePluginMigrationToolkit\Helper\WebsiteSettingsMigrationHelper;
use PimcorePluginMigrationToolkit\OutputWriter\CallbackOutputWriter;
use Doctrine\DBAL\Migrations\Version;
use Pimcore\Migrations\Migration\AbstractPimcoreMigration;
use ReflectionClass;

abstract class AbstractAdvancedPimcoreMigration extends AbstractPimcoreMigration
{
    /** @var string */
    private $dataFolder;

    /** @var SystemSettingsMigrationHelper */
    private $systemSettingsMigrationHelper;

    /** @var LanguageSettingsMigrationHelper */
    private $languageSettingsMigrationHelper;

    /** @var WebsiteSettingsMigrationHelper */
    private $websiteSettingsMigrationHelper;

    /** @var StaticRoutesMigrationHelper */
    private $staticRoutesMigrationHelper;

    /** @var UserRolesMigrationHelper */
    private $userRolesMigrationHelper;

    public function __construct(Version $version)
    {
        parent::__construct($version);

        $reflection       = new ReflectionClass($this);
        $this->dataFolder = 'data/' . str_replace('.php', '', $reflection->getFileName());
    }

    public function getSystemSettingsMigrationHelper(): SystemSettingsMigrationHelper
    {
        if ($this->systemSettingsMigrationHelper === null) {
            $this->systemSettingsMigrationHelper = new SystemSettingsMigrationHelper();
            $this->systemSettingsMigrationHelper->setOutput(
                new CallbackOutputWriter(
                    function ($message) {
                        $this->writeMessage($message);
                    }
                )
            );
        }

        return $this->systemSettingsMigrationHelper;
    }

    public function getLanguageSettingsMigrationHelper(): LanguageSettingsMigrationHelper
    {
        if ($this->languageSettingsMigrationHelper === null) {
            $this->languageSettingsMigrationHelper = new LanguageSettingsMigrationHelper();
            $this->languageSettingsMigrationHelper->setOutput(
                new CallbackOutputWriter(
                    function ($message) {
                        $this->writeMessage($message);
                    }
                )
            );
        }

        return $this->languageSettingsMigrationHelper;
    }

    public function getWebsiteSettingsMigrationHelper(): WebsiteSettingsMigrationHelper
    {
        if ($this->websiteSettingsMigrationHelper === null) {
            $this->websiteSettingsMigrationHelper = new WebsiteSettingsMigrationHelper();
            $this->websiteSettingsMigrationHelper->setOutput(
                new CallbackOutputWriter(
                    function ($message) {
                        $this->writeMessage($message);
                    }
                )
            );
        }

        return $this->websiteSettingsMigrationHelper;
    }

    public function getStaticRoutesMigrationHelper(): StaticRoutesMigrationHelper
    {
        if ($this->staticRoutesMigrationHelper === null) {
            $this->staticRoutesMigrationHelper = new StaticRoutesMigrationHelper();
            $this->staticRoutesMigrationHelper->setOutput(
                new CallbackOutputWriter(
                    function ($message) {
                        $this->writeMessage($message);
                    }
                )
            );
        }

        return $this->staticRoutesMigrationHelper;
    }

    public function getUserRolesMigrationHelper(): UserRolesMigrationHelper
    {
        if ($this->userRolesMigrationHelper === null) {
            $this->userRolesMigrationHelper = new UserRolesMigrationHelper();
            $this->userRolesMigrationHelper->setOutput(
                new CallbackOutputWriter(
                    function ($message) {
                        $this->writeMessage($message);
                    }
                )
            );
        }

        return $this->userRolesMigrationHelper;
    }
}
