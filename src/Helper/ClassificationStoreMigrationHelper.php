<?php

namespace Basilicom\PimcorePluginMigrationToolkit\Helper;

use Basilicom\PimcorePluginMigrationToolkit\Exceptions\NotFoundException;
use Exception;
use Pimcore\Model\DataObject\ClassDefinition\Data as ClassDefinitionData;
use Pimcore\Model\DataObject\Classificationstore;

class ClassificationStoreMigrationHelper extends AbstractMigrationHelper
{
    /**
     * @throws NotFoundException
     */
    public function getStoreByName(string $name): Classificationstore\StoreConfig
    {
        $storeConfig = Classificationstore\StoreConfig::getByName($name);
        if (empty($storeConfig)) {
            $message = sprintf('StoreConfig with name "%s" can not be deleted, because it does not exist.', $name);

            throw new NotFoundException($message);
        }

        return $storeConfig;
    }

    public function createOrUpdateStore(
        string $name,
        string $description
    ): Classificationstore\StoreConfig {
        $storeConfig = Classificationstore\StoreConfig::getByName($name);
        if (empty($storeConfig)) {
            $storeConfig = new Classificationstore\StoreConfig();
        }

        $storeConfig->setName($name);
        $storeConfig->setDescription($description);
        $storeConfig->save();

        return $storeConfig;
    }

    public function deleteStore(string $name): void
    {
        $storeConfig = Classificationstore\StoreConfig::getByName($name);
        if (empty($storeConfig)) {
            $message = sprintf('Store with name "%s" can not be deleted, because it does not exist.', $name);
            $this->getOutput()->writeMessage($message);

            return;
        }

        $storeConfig->delete();
    }

    /**
     * @throws Exception
     */
    public function createOrUpdateGroup(
        string $name,
        string $description,
        int $storeId
    ): Classificationstore\GroupConfig {
        $groupConfig = Classificationstore\GroupConfig::getByName($name, $storeId);
        if (empty($groupConfig)) {
            $groupConfig = new Classificationstore\GroupConfig();
            $groupConfig->setStoreId($storeId);
            $groupConfig->setName($name);
        }
        $groupConfig->setDescription($description);
        $groupConfig->save();

        $this->clearCache();

        return $groupConfig;
    }

    /**
     * @throws Exception
     */
    public function renameGroup(string $oldName, string $newName, int $storeId): ?Classificationstore\GroupConfig
    {
        $groupConfig = Classificationstore\GroupConfig::getByName($oldName, $storeId);
        $groupConfig->setName($newName);
        $groupConfig->save();

        $this->clearCache();

        return $groupConfig;
    }

    /**
     * @throws Exception
     */
    public function deleteGroup(string $name, int $storeId): void
    {
        $groupConfig = Classificationstore\GroupConfig::getByName($name, $storeId);
        if (empty($groupConfig)) {
            $message = sprintf('Group with name "%s" (store %s) can not be deleted, because it does not exist.', $name, $storeId);
            $this->getOutput()->writeMessage($message);

            return;
        }

        $groupConfig->delete();
    }

    /**
     * @throws Exception
     */
    public function createOrUpdateKey(
        string $name,
        string $title,
        string $description,
        ClassDefinitionData $fieldDefinition,
        int $storeId,
        string $groupName,
    ): void {
        $keyConfig = Classificationstore\KeyConfig::getByName($name, $storeId);
        if (empty($keyConfig)) {
            $keyConfig = new Classificationstore\KeyConfig();
        }

        $keyConfig->setName($name);
        $keyConfig->setTitle($title);
        $keyConfig->setDescription($description);
        $keyConfig->setEnabled(true);
        $keyConfig->setType($fieldDefinition->getFieldtype());
        $keyConfig->setDefinition(json_encode($fieldDefinition));
        $keyConfig->setStoreId($storeId);
        $keyConfig->save();

        $groupConfig = Classificationstore\GroupConfig::getByName($groupName, $storeId);
        $keyGroupRelation = new Classificationstore\KeyGroupRelation();
        $keyGroupRelation->setKeyId($keyConfig->getId());
        $keyGroupRelation->setGroupId($groupConfig->getId());
        $keyGroupRelation->save();
    }

    /**
     * @throws Exception
     */
    public function deleteKey(string $name, int $storeId): void
    {
        $keyConfig = Classificationstore\KeyConfig::getByName($name, $storeId);
        if (empty($keyConfig)) {
            $message = sprintf('Key with name "%s" (store %s) can not be deleted, because it does not exist.', $name, $storeId);
            $this->getOutput()->writeMessage($message);

            return;
        }

        $keyConfig->delete();
    }
}
