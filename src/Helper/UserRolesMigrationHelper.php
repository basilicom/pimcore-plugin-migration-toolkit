<?php

namespace Basilicom\PimcorePluginMigrationToolkit\Helper;

use Basilicom\PimcorePluginMigrationToolkit\Exceptions\InvalidSettingException;
use Exception;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\Document;
use Pimcore\Model\User\Role;
use Pimcore\Model\User\Workspace\Asset as WorkspaceAsset;
use Pimcore\Model\User\Workspace\DataObject as WorkspaceDataObject;
use Pimcore\Model\User\Workspace\Document as WorkspaceDocument;

class UserRolesMigrationHelper extends AbstractMigrationHelper
{
    /**
     * @param string $name
     * @param array $permissions see database table users_permission_definitions
     * @param array $docTypes
     * @param array $classes
     * @param array $viewWebsiteTranslations
     * @param array $editWebsiteTranslations
     * @param array $perspectives
     * @param int $parentId
     *
     * @throws InvalidSettingException
     * @throws Exception
     */
    public function create(
        string $name,
        array $permissions = [],
        array $docTypes = [],
        array $classes = [],
        array $viewWebsiteTranslations = [],
        array $editWebsiteTranslations = [],
        array $perspectives = [],
        int $parentId = 0
    ): void {
        $role = Role::getByName($name);

        if ($role) {
            $message = sprintf('Not creating User Role with name "%s". User Role with this name already exists.', $name);

            throw new InvalidSettingException($message);
        }

        $role = new Role();
        $role->setParentId($parentId);
        $role->setName($name);
        $role->setPermissions($permissions);
        $role->setDocTypes($docTypes);
        $role->setClasses($classes);
        $role->setPerspectives($perspectives);

        $this->addSharedTranslationSettings($role, $viewWebsiteTranslations, $editWebsiteTranslations);

        $role->save();

        $this->clearCache();
    }

    /**
     * @param string $name
     * @param array $permissions see database table users_permission_definitions
     * @param array $docTypes
     * @param array $classes
     * @param array $viewWebsiteTranslations
     * @param array $editWebsiteTranslations
     * @param array $perspectives
     * @param int $parentId
     *
     * @throws InvalidSettingException
     * @throws Exception
     */
    public function update(
        string $name,
        array $permissions = [],
        array $docTypes = [],
        array $classes = [],
        array $viewWebsiteTranslations = [],
        array $editWebsiteTranslations = [],
        array $perspectives = [],
        int $parentId = 0
    ): void {
        $role = $this->getRole($name, 'Not updating User Role with name "%s". User Role with this name does not exists.');

        if (!empty($parentId)) {
            $role->setParentId($parentId);
        }

        if (!empty($permissions)) {
            $role->setPermissions($permissions);
        }

        if (!empty($docTypes)) {
            $role->setDocTypes($docTypes);
        }

        if (!empty($classes)) {
            $role->setClasses($classes);
        }

        if (!empty($perspectives)) {
            $role->setPerspectives($perspectives);
        }

        if (!empty($viewWebsiteTranslations) || !empty($editWebsiteTranslations)) {
            $this->addSharedTranslationSettings($role, $viewWebsiteTranslations, $editWebsiteTranslations);
        }

        $role->save();

        $this->clearCache();
    }

    /**
     * @throws InvalidSettingException
     */
    public function addWorkspaceDataObject(
        string $roleName,
        string $path,
        bool $list = false,
        bool $view = false,
        bool $save = false,
        bool $publish = false,
        bool $unpublish = false,
        bool $delete = false,
        bool $rename = false,
        bool $create = false,
        bool $settings = false,
        bool $versions = false,
        bool $properties = false,
        string $layouts = null,
        string $lEdit = null,
        string $lView = null,
    ): void {
        $role = $this->getRole($roleName, 'Not adding WorkspaceDataObject to User Role with name "%s", because User Role does not exists.');

        $dataObject = DataObject::getByPath($path);

        if (empty($dataObject)) {
            $message = sprintf(
                'Not adding WorkspaceDataObject to User Role with name "%s", because the object path "%s" does not exists.',
                $roleName,
                $path
            );

            throw new InvalidSettingException($message);
        }

        $workspaceDataObject = new WorkspaceDataObject();
        $workspaceDataObject->setUserId($role->getId());
        $workspaceDataObject->setCid($dataObject->getId());
        $workspaceDataObject->setCpath($dataObject->getFullPath());
        $workspaceDataObject->setList($list);
        $workspaceDataObject->setView($view);
        $workspaceDataObject->setSave($save);
        $workspaceDataObject->setPublish($publish);
        $workspaceDataObject->setUnpublish($unpublish);
        $workspaceDataObject->setDelete($delete);
        $workspaceDataObject->setRename($rename);
        $workspaceDataObject->setCreate($create);
        $workspaceDataObject->setSettings($settings);
        $workspaceDataObject->setVersions($versions);
        $workspaceDataObject->setProperties($properties);
        $workspaceDataObject->setLayouts($layouts);
        $workspaceDataObject->setLEdit($lEdit);
        $workspaceDataObject->setLView($lView);
        $workspaceDataObject->save();
    }

    /**
     * @throws InvalidSettingException
     * @throws Exception
     */
    public function updateWorkspaceDataObject(
        string $roleName,
        string $path,
        bool $list = false,
        bool $view = false,
        bool $save = false,
        bool $publish = false,
        bool $unpublish = false,
        bool $delete = false,
        bool $rename = false,
        bool $create = false,
        bool $settings = false,
        bool $versions = false,
        bool $properties = false,
        string $layouts = null,
        string $lEdit = null,
        string $lView = null,
    ): void {
        $role = $this->getRole($roleName, 'Not updating WorkspaceDataObject of User Role with name "%s", because User Role does not exists.');

        $workspaceElements = $role->getWorkspacesObject();
        $workspaceElementExists = false;

        foreach ($workspaceElements as $workspaceElement) {
            if ($workspaceElement->getCpath() === $path) {
                $workspaceElement->setList($list);
                $workspaceElement->setView($view);
                $workspaceElement->setSave($save);
                $workspaceElement->setPublish($publish);
                $workspaceElement->setUnpublish($unpublish);
                $workspaceElement->setDelete($delete);
                $workspaceElement->setRename($rename);
                $workspaceElement->setCreate($create);
                $workspaceElement->setSettings($settings);
                $workspaceElement->setVersions($versions);
                $workspaceElement->setProperties($properties);
                $workspaceElement->setLayouts($layouts);
                $workspaceElement->setLEdit($lEdit);
                $workspaceElement->setLView($lView);

                $workspaceElementExists = true;

                break;
            }
        }

        if (!$workspaceElementExists) {
            $message = sprintf(
                'Not updating WorkspaceDataObject of User Role with name "%s", because the workspace path "%s" does not exists.',
                $roleName,
                $path
            );

            throw new InvalidSettingException($message);
        }

        $role->setWorkspacesObject($workspaceElements);
        $role->save();

        $this->clearCache();
    }

    /**
     * @throws InvalidSettingException
     * @throws Exception
     */
    public function deleteWorkspaceDataObject(string $roleName, string $path): void
    {
        $role = $this->getRole($roleName, 'Not deleting WorkspaceDataObject of User Role with name "%s", because User Role does not exists.');

        $workspaceElements = $role->getWorkspacesObject();
        $workspaceElementExists = false;

        foreach ($workspaceElements as $key => $workspaceElement) {
            if ($workspaceElement->getCpath() === $path) {
                unset($workspaceElements[$key]);

                $workspaceElementExists = true;

                break;
            }
        }

        if (!$workspaceElementExists) {
            $message = sprintf(
                'Not deleting WorkspaceDataObject of User Role with name "%s", because the workspace path "%s" does not exists.',
                $roleName,
                $path
            );

            $this->getOutput()->writeMessage($message);
        }

        $role->setWorkspacesObject($workspaceElements);
        $role->save();

        $this->clearCache();
    }

    /**
     * @throws InvalidSettingException
     */
    public function addWorkspaceDocument(
        string $roleName,
        string $path,
        bool $list = false,
        bool $view = false,
        bool $save = false,
        bool $publish = false,
        bool $unpublish = false,
        bool $delete = false,
        bool $rename = false,
        bool $create = false,
        bool $settings = false,
        bool $versions = false,
        bool $properties = false
    ): void {
        $role = $this->getRole($roleName, 'Not adding WorkspaceDocument to User Role with name "%s", because User Role does not exists.');

        $document = Document::getByPath($path);

        if (empty($document)) {
            $message = sprintf(
                'Not adding WorkspaceDocument to User Role with name "%s", because the document path "%s" does not exists.',
                $roleName,
                $path
            );

            throw new InvalidSettingException($message);
        }

        $workspaceDocument = new WorkspaceDocument();
        $workspaceDocument->setUserId($role->getId());
        $workspaceDocument->setCid($document->getId());
        $workspaceDocument->setCpath($document->getFullPath());
        $workspaceDocument->setList($list);
        $workspaceDocument->setView($view);
        $workspaceDocument->setSave($save);
        $workspaceDocument->setPublish($publish);
        $workspaceDocument->setUnpublish($unpublish);
        $workspaceDocument->setDelete($delete);
        $workspaceDocument->setRename($rename);
        $workspaceDocument->setCreate($create);
        $workspaceDocument->setSettings($settings);
        $workspaceDocument->setVersions($versions);
        $workspaceDocument->setProperties($properties);
        $workspaceDocument->save();
    }

    /**
     * @throws InvalidSettingException
     * @throws Exception
     */
    public function updateWorkspaceDocument(
        string $roleName,
        string $path,
        bool $list = false,
        bool $view = false,
        bool $save = false,
        bool $publish = false,
        bool $unpublish = false,
        bool $delete = false,
        bool $rename = false,
        bool $create = false,
        bool $settings = false,
        bool $versions = false,
        bool $properties = false
    ): void {
        $role = $this->getRole($roleName, 'Not updating WorkspaceDocument of User Role with name "%s", because User Role does not exists.');

        $workspaceElements = $role->getWorkspacesDocument();
        $workspaceElementExists = false;

        foreach ($workspaceElements as $workspaceElement) {
            if ($workspaceElement->getCpath() === $path) {
                $workspaceElement->setList($list);
                $workspaceElement->setView($view);
                $workspaceElement->setSave($save);
                $workspaceElement->setPublish($publish);
                $workspaceElement->setUnpublish($unpublish);
                $workspaceElement->setDelete($delete);
                $workspaceElement->setRename($rename);
                $workspaceElement->setCreate($create);
                $workspaceElement->setSettings($settings);
                $workspaceElement->setVersions($versions);
                $workspaceElement->setProperties($properties);

                $workspaceElementExists = true;

                break;
            }
        }

        if (!$workspaceElementExists) {
            $message = sprintf(
                'Not updating WorkspaceDocument of User Role with name "%s", because the workspace path "%s" does not exists.',
                $roleName,
                $path
            );

            throw new InvalidSettingException($message);
        }

        $role->setWorkspacesDocument($workspaceElements);
        $role->save();

        $this->clearCache();
    }

    /**
     * @throws InvalidSettingException
     * @throws Exception
     */
    public function deleteWorkspaceDocument(string $roleName, string $path): void
    {
        $role = $this->getRole($roleName, 'Not deleting WorkspaceDocument of User Role with name "%s", because User Role does not exists.');

        $workspaceElements = $role->getWorkspacesDocument();
        $workspaceElementExists = false;

        foreach ($workspaceElements as $key => $workspaceElement) {
            if ($workspaceElement->getCpath() === $path) {
                unset($workspaceElements[$key]);

                $workspaceElementExists = true;

                break;
            }
        }

        if (!$workspaceElementExists) {
            $message = sprintf(
                'Not deleting WorkspaceDocument of User Role with name "%s", because the workspace path "%s" does not exists.',
                $roleName,
                $path
            );

            $this->getOutput()->writeMessage($message);
        }

        $role->setWorkspacesDocument($workspaceElements);
        $role->save();

        $this->clearCache();
    }

    /**
     * @throws InvalidSettingException
     */
    public function addWorkspaceAsset(
        string $roleName,
        string $path,
        bool $list = false,
        bool $view = false,
        bool $publish = false,
        bool $delete = false,
        bool $rename = false,
        bool $create = false,
        bool $settings = false,
        bool $versions = false,
        bool $properties = false
    ): void {
        $role = $this->getRole($roleName, 'Not adding WorkspaceAsset to User Role with name "%s", because User Role does not exists.');

        $asset = Asset::getByPath($path);

        if (empty($asset)) {
            $message = sprintf(
                'Not adding WorkspaceAsset to User Role with name "%s", because the asset path "%s" does not exists.',
                $roleName,
                $path
            );

            throw new InvalidSettingException($message);
        }

        $workspaceAsset = new WorkspaceAsset();
        $workspaceAsset->setUserId($role->getId());
        $workspaceAsset->setCid($asset->getId());
        $workspaceAsset->setCpath($asset->getFullPath());
        $workspaceAsset->setList($list);
        $workspaceAsset->setView($view);
        $workspaceAsset->setPublish($publish);
        $workspaceAsset->setDelete($delete);
        $workspaceAsset->setRename($rename);
        $workspaceAsset->setCreate($create);
        $workspaceAsset->setSettings($settings);
        $workspaceAsset->setVersions($versions);
        $workspaceAsset->setProperties($properties);
        $workspaceAsset->save();
    }

    /**
     * @throws InvalidSettingException
     * @throws Exception
     */
    public function updateWorkspaceAsset(
        string $roleName,
        string $path,
        bool $list = false,
        bool $view = false,
        bool $publish = false,
        bool $delete = false,
        bool $rename = false,
        bool $create = false,
        bool $settings = false,
        bool $versions = false,
        bool $properties = false
    ): void {
        $role = $this->getRole($roleName, 'Not updating WorkspaceAsset of User Role with name "%s", because User Role does not exists.');

        $workspaceElements = $role->getWorkspacesAsset();
        $workspaceElementExists = false;

        foreach ($workspaceElements as $workspaceElement) {
            if ($workspaceElement->getCpath() === $path) {
                $workspaceElement->setList($list);
                $workspaceElement->setView($view);
                $workspaceElement->setPublish($publish);
                $workspaceElement->setDelete($delete);
                $workspaceElement->setRename($rename);
                $workspaceElement->setCreate($create);
                $workspaceElement->setSettings($settings);
                $workspaceElement->setVersions($versions);
                $workspaceElement->setProperties($properties);

                $workspaceElementExists = true;

                break;
            }
        }

        if (!$workspaceElementExists) {
            $message = sprintf(
                'Not updating WorkspaceAsset of User Role with name "%s", because the workspace path "%s" does not exists.',
                $roleName,
                $path
            );

            throw new InvalidSettingException($message);
        }

        $role->setWorkspacesAsset($workspaceElements);
        $role->save();

        $this->clearCache();
    }

    /**
     * @throws InvalidSettingException
     * @throws Exception
     */
    public function deleteWorkspaceAsset(string $roleName, string $path): void
    {
        $role = $this->getRole($roleName, 'Not deleting WorkspaceAsset of User Role with name "%s", because User Role does not exists.');

        $workspaceElements = $role->getWorkspacesAsset();
        $workspaceElementExists = false;

        foreach ($workspaceElements as $key => $workspaceElement) {
            if ($workspaceElement->getCpath() === $path) {
                unset($workspaceElements[$key]);

                $workspaceElementExists = true;

                break;
            }
        }

        if (!$workspaceElementExists) {
            $message = sprintf(
                'Not deleting WorkspaceAsset of User Role with name "%s", because the workspace path "%s" does not exists.',
                $roleName,
                $path
            );

            $this->getOutput()->writeMessage($message);
        }

        $role->setWorkspacesAsset($workspaceElements);
        $role->save();

        $this->clearCache();
    }

    /**
     * @throws Exception
     */
    public function delete(string $name): void
    {
        $role = Role::getByName($name);

        if (empty($role)) {
            $message = sprintf('User Role with name "%s" can not be deleted, because it does not exist.', $name);
            $this->getOutput()->writeMessage($message);

            return;
        }

        $role->delete();
    }

    private function addSharedTranslationSettings(
        Role $role,
        array $viewWebsiteTranslations,
        array $editWebsiteTranslations
    ): void {
        $messageTemplate = 'The language "%s" is not a valid language and cannot be set for user roles.';

        foreach ($viewWebsiteTranslations as $key => $language) {
            if (!$this->isValidLanguage($language)) {
                $message = sprintf(
                    $messageTemplate,
                    $language
                );
                $this->getOutput()->writeMessage($message);
                unset($viewWebsiteTranslations[$key]);
            }
        }

        foreach ($editWebsiteTranslations as $key => $language) {
            if (!$this->isValidLanguage($language)) {
                $message = sprintf(
                    $messageTemplate,
                    $language
                );
                $this->getOutput()->writeMessage($message);
                unset($editWebsiteTranslations[$key]);
            }
        }

        $role->setWebsiteTranslationLanguagesView($viewWebsiteTranslations);
        $role->setWebsiteTranslationLanguagesEdit($editWebsiteTranslations);
    }

    /**
     * @throws InvalidSettingException
     */
    private function getRole(string $roleName, string $messageTemplate): Role
    {
        $role = Role::getByName($roleName);

        if (empty($role)) {
            $message = sprintf(
                $messageTemplate,
                $roleName
            );

            throw new InvalidSettingException($message);
        }

        return $role;
    }
}
