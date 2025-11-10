<?php

namespace Basilicom\PimcorePluginMigrationToolkit\Helper;

use Basilicom\PimcorePluginMigrationToolkit\Exceptions\InvalidSettingException;
use Exception;
use Pimcore\Model\DataObject\ClassDefinition\Service;
use Pimcore\Model\DataObject\Fieldcollection\Definition as FieldcollectionDefinition;

class FieldcollectionMigrationHelper extends AbstractMigrationHelper
{
    protected string $dataFolder;

    public function __construct(string $dataFolder)
    {
        $this->dataFolder = $dataFolder;
    }

    /**
     * @throws InvalidSettingException
     * @throws Exception
     */
    public function createOrUpdate(string $key, string $pathToJsonConfig): void
    {
        if (!file_exists($pathToJsonConfig)) {
            $message = sprintf(
                'The Fieldcollection "%s" could not be created, because the json file "%s" does not exist.',
                $key,
                $pathToJsonConfig
            );

            throw new InvalidSettingException($message);
        }

        $fieldcollection = FieldcollectionDefinition::getByKey($key);
        if (empty($fieldcollection)) {
            $fieldcollection = $this->create($key);
        }

        $configJson = file_get_contents($pathToJsonConfig);
        Service::importFieldCollectionFromJson($fieldcollection, $configJson);

        $this->clearCache();
    }

    /**
     * @throws InvalidSettingException
     */
    private function create(string $key): FieldcollectionDefinition
    {
        try {
            $definition = new FieldcollectionDefinition();
            $definition->setKey($key);
            $definition->save();

            return $definition;
        } catch (Exception $exception) {
            $message = sprintf(
                'Fieldcollection "%s" could not be created.',
                $key
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
    public function delete(string $key): void
    {
        $fieldcollection = FieldcollectionDefinition::getByKey($key);

        if (empty($fieldcollection)) {
            $message = sprintf('Fieldcollection "%s" can not be deleted, because it does not exist.', $key);
            $this->getOutput()->writeMessage($message);

            return;
        }

        $fieldcollection->delete();
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
        $dataFolder .= 'fieldcollection_' . $className . '_export.json';

        return $dataFolder;
    }
}
