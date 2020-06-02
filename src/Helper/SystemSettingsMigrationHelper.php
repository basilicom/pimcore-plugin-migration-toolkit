<?php

namespace Basilicom\PimcorePluginMigrationToolkit\Helper;

use Basilicom\PimcorePluginMigrationToolkit\Exceptions\InvalidSettingException;
use Pimcore\Config;
use Pimcore\File;
use Symfony\Component\Yaml\Yaml;

class SystemSettingsMigrationHelper extends AbstractMigrationHelper
{
    /** @var string */
    private $configFile;

    /** @var array */
    private $systemConfig;

    const SETTINGS_PIMCORE       = 'pimcore';
    const SETTINGS_ASSETS        = 'assets';
    const SETTINGS_PIMCORE_ADMIN = 'pimcore_admin';
    const SETTING_BRANDING       = 'branding';

    public function __construct()
    {
        $this->configFile   = Config::locateConfigFile('system.yml');
        $this->systemConfig = Yaml::parseFile($this->configFile);
    }

    private function saveSystemSettings(): void
    {
        $settingsYml = Yaml::dump($this->systemConfig, 5);
        File::put($this->configFile, $settingsYml);
    }

    public function setHideEditImageTab(bool $value = false): void
    {
        if (!isset($this->systemConfig[self::SETTINGS_PIMCORE][self::SETTINGS_ASSETS])) {
            $this->systemConfig[self::SETTINGS_PIMCORE][self::SETTINGS_ASSETS] = [];
        }
        $this->systemConfig[self::SETTINGS_PIMCORE][self::SETTINGS_ASSETS]['hide_edit_image'] = $value;
        $this->saveSystemSettings();
    }

    public function removeHideEditImageTab(): void
    {
        $this->setHideEditImageTab();
    }

    /**
     * @param string $color
     *
     * @throws InvalidSettingException
     */
    public function setLoginColor(string $color): void
    {
        if (!isset($this->systemConfig[self::SETTINGS_PIMCORE_ADMIN][self::SETTING_BRANDING])) {
            $this->systemConfig[self::SETTINGS_PIMCORE_ADMIN][self::SETTING_BRANDING] = [];
        }

        $this->assertColorIsValid($color);
        $this->systemConfig[self::SETTINGS_PIMCORE_ADMIN][self::SETTING_BRANDING]['color_login_screen'] = $color;
        $this->saveSystemSettings();
    }

    public function removeLoginColor(): void
    {
        if (!isset($this->systemConfig[self::SETTINGS_PIMCORE_ADMIN][self::SETTING_BRANDING])) {
            $this->systemConfig[self::SETTINGS_PIMCORE_ADMIN][self::SETTING_BRANDING] = [];
        }

        $this->systemConfig[self::SETTINGS_PIMCORE_ADMIN][self::SETTING_BRANDING]['color_login_screen'] = '';
        $this->saveSystemSettings();
    }

    /**
     * @param string $color
     *
     * @throws InvalidSettingException
     */
    public function setAdminColor(string $color): void
    {
        if (!isset($this->systemConfig[self::SETTINGS_PIMCORE_ADMIN][self::SETTING_BRANDING])) {
            $this->systemConfig[self::SETTINGS_PIMCORE_ADMIN][self::SETTING_BRANDING] = [];
        }

        $this->assertColorIsValid($color);
        $this->systemConfig[self::SETTINGS_PIMCORE_ADMIN][self::SETTING_BRANDING]['color_admin_interface'] = $color;
        $this->saveSystemSettings();
    }

    public function removeAdminColor(): void
    {
        if (!isset($this->systemConfig[self::SETTINGS_PIMCORE_ADMIN][self::SETTING_BRANDING])) {
            $this->systemConfig[self::SETTINGS_PIMCORE_ADMIN][self::SETTING_BRANDING] = [];
        }

        $this->systemConfig[self::SETTINGS_PIMCORE_ADMIN][self::SETTING_BRANDING]['color_admin_interface'] = '';
        $this->saveSystemSettings();
    }

    /**
     * @param string $color
     *
     * @throws InvalidSettingException
     */
    private function assertColorIsValid(string $color): void
    {
        $isColorValid = boolval(preg_match('/#([a-f0-9]{3}){1,2}\b/i', $color));
        if ($isColorValid === false) {
            $exceptionMessage = sprintf(
                'The color "%s" is not valid.',
                $color
            );
            throw new InvalidSettingException($exceptionMessage);
        }
    }

    public function setInvertColorsForLoginScreen(bool $value = false): void
    {
        if (!isset($this->systemConfig[self::SETTINGS_PIMCORE_ADMIN][self::SETTING_BRANDING])) {
            $this->systemConfig[self::SETTINGS_PIMCORE_ADMIN][self::SETTING_BRANDING] = [];
        }

        $this->systemConfig[self::SETTINGS_PIMCORE_ADMIN][self::SETTING_BRANDING]['login_screen_invert_colors'] = $value;
        $this->saveSystemSettings();
    }

    public function removeInvertColorsForLoginScreen(): void
    {
        $this->setInvertColorsForLoginScreen();
    }

    public function setLoginScreenCustomImage(string $path): void
    {
        if (empty($path)) {
            $exceptionMessage = 'The login screen custom image cannot be set, because the path is empty.';
            throw new InvalidSettingException($exceptionMessage);
        }

        $this->systemConfig[self::SETTINGS_PIMCORE_ADMIN][self::SETTING_BRANDING]['login_screen_custom_image'] = $path;
        $this->saveSystemSettings();

    }

    public function removeLoginScreenCustomImage(): void
    {
        $this->systemConfig[self::SETTINGS_PIMCORE_ADMIN][self::SETTING_BRANDING]['login_screen_custom_image'] = '';
        $this->saveSystemSettings();

    }
}
