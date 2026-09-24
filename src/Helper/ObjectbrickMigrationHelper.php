<?php

declare(strict_types=1);

namespace Basilicom\PimcorePluginMigrationToolkit\Helper;

use Basilicom\PimcorePluginMigrationToolkit\Exceptions\InvalidSettingException;
use Exception;
use Pimcore\Model\DataObject\ClassDefinition\Service;
use Pimcore\Model\DataObject\Objectbrick\Definition as ObjectbrickDefinition;

class ObjectbrickMigrationHelper extends AbstractMigrationHelper
{
    protected string $dataFolder;

    public function __construct(string $dataFolder)
    {
        $this->dataFolder = $dataFolder;
    }

    /** @throws InvalidSettingException */
    public function createOrUpdate(string $key, string $pathToJsonConfig): void
    {
        if (!file_exists($pathToJsonConfig)) {
            $message = sprintf(
                'The Objectbrick "%s" could not be created, because the json file "%s" does not exist.',
                $key,
                $pathToJsonConfig
            );

            throw new InvalidSettingException($message);
        }

        $objectbrick = ObjectbrickDefinition::getByKey($key);
        if (empty($objectbrick)) {
            $objectbrick = $this->create($key);
        }

        $configJson = file_get_contents($pathToJsonConfig);
        Service::importObjectBrickFromJson($objectbrick, $configJson);
    }

    /** @throws InvalidSettingException */
    private function create(string $key): ObjectbrickDefinition
    {
        try {
            $definition = new ObjectbrickDefinition();
            $definition->setKey($key);
            $definition->save();

            return $definition;
        } catch (Exception $exception) {
            $message = sprintf(
                'Objectbrick "%s" could not be created.',
                $key
            );

            throw new InvalidSettingException(
                $message,
                0,
                $exception
            );
        }
    }

    public function delete(string $key): void
    {
        $objectbricks = ObjectbrickDefinition::getByKey($key);

        if (empty($objectbricks)) {
            $message = sprintf('Objectbrick "%s" can not be deleted, because it does not exist.', $key);
            $this->getOutput()->writeMessage($message);

            return;
        }

        $objectbricks->delete();
    }

    public function getJsonDefinitionPathForUpMigration(string $className): string
    {
        return $this->getJsonFileNameFor($className, self::UP);
    }

    public function getJsonDefinitionPathForDownMigration(string $className): string
    {
        return $this->getJsonFileNameFor($className, self::DOWN);
    }

    private function getJsonFileNameFor(string $className, string $direction): string
    {
        $dataFolder = $this->dataFolder;
        if ($direction === self::DOWN) {
            $dataFolder .= '/down/';
        } else {
            $dataFolder .= '/';
        }
        $dataFolder .= 'objectbrick_' . $className . '_export.json';

        return $dataFolder;
    }
}
