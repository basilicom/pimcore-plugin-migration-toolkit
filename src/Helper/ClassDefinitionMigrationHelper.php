<?php

namespace Basilicom\PimcorePluginMigrationToolkit\Helper;

use Basilicom\PimcorePluginMigrationToolkit\Exceptions\InvalidSettingException;
use Exception;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Service;

class ClassDefinitionMigrationHelper extends AbstractMigrationHelper
{
    protected string $dataFolder;

    public function __construct(string $dataFolder)
    {
        $this->dataFolder = $dataFolder;
    }

    /**
     * @throws InvalidSettingException
     */
    public function createOrUpdate(string $className, string $pathToJsonConfig, ?string $id = null)
    {
        if (!file_exists($pathToJsonConfig)) {
            $message = sprintf(
                'The Class Definition "%s" could not be created, because the json file "%s" does not exist.',
                $className,
                $pathToJsonConfig
            );
            throw new InvalidSettingException($message);
        }

        $configJson = file_get_contents($pathToJsonConfig);
        $classConfig = json_decode($configJson, true);

        if (!empty($id)) {
            $class = ClassDefinition::getById($id) ?? ClassDefinition::getByName($className);
        } else {
            $id = $classConfig['id'];
            $class = ClassDefinition::getById($id);
        }

        if (empty($class)) {
            $class = $this->create($id, $className);
        }

        Service::importClassDefinitionFromJson($class, $configJson, true);

        $this->clearCache();
    }

    /**
     * @throws InvalidSettingException
     */
    private function create(string $id, string $className): ClassDefinition
    {
        try {
            $values = [
                'id'        => empty($id) ? mb_strtolower($className) : $id,
                'name'      => $className,
                'userOwner' => 0,
            ];

            $class = ClassDefinition::create($values);
            $class->save();

            return $class;
        } catch (Exception $exception) {
            $message = sprintf(
                'Class Definition "%s" could not be created.',
                $className
            );
            throw new InvalidSettingException(
                $message,
                0,
                $exception
            );
        }
    }

    /**
     * @throws Exception
     */
    public function delete(string $className): void
    {
        $classDefinition = ClassDefinition::getByName($className);

        if (empty($classDefinition)) {
            $message = sprintf(
                'Class Definition with name "%s" can not be deleted, because it does not exist.',
                $className
            );
            $this->getOutput()->writeMessage($message);
            return;
        }

        $classDefinition->delete();
    }

    public function getJsonDefinitionPathForUpMigration($className): string
    {
        return $this->getJsonFileNameFor($className, self::UP);
    }

    public function getJsonDefinitionPathForDownMigration($className): string
    {
        return $this->getJsonFileNameFor($className, self::DOWN);
    }

    private function getJsonFileNameFor($className, string $direction): string
    {
        $dataFolder = $direction === self::DOWN ? $this->dataFolder . '/down/' : $this->dataFolder . '/';
        $dataFolder .= 'class_' . $className . '_export.json';

        return $dataFolder;
    }
}
