<?php

namespace Basilicom\PimcorePluginMigrationToolkit\Helper;

use Basilicom\PimcorePluginMigrationToolkit\Exceptions\InvalidSettingException;
use Exception;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Service as ObjectService;

class DataObjectMigrationHelper extends AbstractMigrationHelper
{
    // bastodo: add support for class objects

    /**
     * @throws InvalidSettingException
     * @throws Exception
     */
    public function createFolderByParentId(string $name, int $parentId): void
    {
        $parent = DataObject::getById($parentId);

        if (empty($parent)) {
            $message = sprintf(
                'The Folder "%s" could not be created, because the parent does not exist',
                $name
            );

            throw new InvalidSettingException($message);
        }

        $intendedPath = $parent->getRealFullPath() . '/' . $name;

        $this->createFolderByPath($intendedPath);
    }

    /**
     * @throws InvalidSettingException
     */
    public function createFolderByPath(string $path): void
    {
        try {
            ObjectService::createFolderByPath($path);
        } catch (Exception) {
            $message = sprintf('The Folder "%s" could not be created.', $path);

            throw new InvalidSettingException($message);
        }
    }

    /**
     * @throws InvalidSettingException
     * @throws Exception
     */
    public function deleteById(int $id): void
    {
        if ($id === 1) {
            throw new InvalidSettingException('You cannot delete the root object.');
        }

        $object = DataObject::getById($id);

        if (empty($object)) {
            $message = sprintf('Object with id "%s" can not be deleted, because it does not exist.', $id);
            $this->getOutput()->writeMessage($message);

            return;
        }

        $object->delete();
    }

    /**
     * @throws InvalidSettingException
     * @throws Exception
     */
    public function deleteByPath(string $path): void
    {
        if (empty($path)) {
            throw new InvalidSettingException('Object can not be deleted, because path needs to be defined');
        }

        $object = DataObject::getByPath($path);

        if (empty($object)) {
            $message = sprintf('Object with path "%s" can not be deleted, because it does not exist.', $path);
            $this->getOutput()->writeMessage($message);

            return;
        }

        $object->delete();
    }
}
