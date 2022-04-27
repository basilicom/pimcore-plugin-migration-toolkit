<?php

namespace Basilicom\PimcorePluginMigrationToolkit\Helper;

use Basilicom\PimcorePluginMigrationToolkit\Exceptions\InvalidSettingException;
use Exception;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Service;

class ClassDefinitionMigrationHelper extends AbstractMigrationHelper
{
    /** @var string */
    protected $dataFolder;

    public function __construct(string $dataFolder)
    {
        $this->dataFolder = $dataFolder;
    }

    /**
     * @param string $className
     * @param string $pathToJsonConfig
     *
     * @throws InvalidSettingException
     */
    public function createOrUpdate(string $className, string $pathToJsonConfig)
    {
        if (!file_exists($pathToJsonConfig)) {
            $message = sprintf(
                'The Class Definition "%s" could not be created, because the json file "%s" does not exist.',
                $className,
                $pathToJsonConfig
            );
            throw new InvalidSettingException($message);
        }

        $class = ClassDefinition::getByName($className);
        $configJson = file_get_contents($pathToJsonConfig);

        if (empty($class)) {
            $classConfig = json_decode($configJson, true);
            $class = $this->create($classConfig['id'], $className);
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
                'name' => $className,
                'userOwner' => 0,
                'id' => $id,
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
        $dataFolder = $this->dataFolder;
        if ($direction === self::DOWN) {
            $dataFolder .= '/down/';
        } else {
            $dataFolder .= '/';
        }
        $dataFolder .= 'class_' . $className . '_export.json';

        return $dataFolder;
    }
}
