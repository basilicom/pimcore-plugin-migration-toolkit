<?php

declare(strict_types=1);

namespace Basilicom\PimcorePluginMigrationToolkit\Helper;

use Basilicom\PimcorePluginMigrationToolkit\Exceptions\InvalidSettingException;
use Exception;
use Pimcore\Model\DataObject\ClassDefinition\CustomLayout;
use Pimcore\Model\DataObject\ClassDefinition\Service;
use Symfony\Component\Serializer\Encoder\DecoderInterface;

class CustomLayoutMigrationHelper extends AbstractMigrationHelper
{
    protected string $dataFolder;
    protected DecoderInterface $serializer;

    public function __construct(string $dataFolder, DecoderInterface $serializer)
    {
        $this->dataFolder = $dataFolder;
        $this->serializer = $serializer;
    }

    /**
     * @throws InvalidSettingException
     * @throws Exception
     */
    public function createOrUpdate(string $layoutName, string $classId, string $pathToJsonConfig): void
    {
        if (!file_exists($pathToJsonConfig)) {
            $message = sprintf(
                'The Custom Layout "%s" for classId "%s" could not be created, because the json file "%s" does not exist.',
                $layoutName,
                $classId,
                $pathToJsonConfig
            );

            throw new InvalidSettingException($message);
        }

        $customLayout = $this->find($layoutName, $classId) ?? $this->create($layoutName, $classId);

        try {
            $configJson       = $this->decodeJson((string)file_get_contents($pathToJsonConfig));
            $layoutDefinition = Service::generateLayoutTreeFromArray($configJson['layoutDefinitions'], true);
            $customLayout->setLayoutDefinitions($layoutDefinition);
            $customLayout->setDescription($configJson['description']);
            $customLayout->setDefault($configJson['default']);
            $customLayout->save();
            $this->forgetRuntimeCache();
        } catch (Exception $exception) {
            $message = sprintf(
                'Custom Layout "%s" for classId "%s" could not be saved.',
                $layoutName,
                $classId
            );

            throw new InvalidSettingException(
                $message,
                0,
                $exception
            );
        }
    }

    /** @throws InvalidSettingException */
    private function create(string $layoutName, string $classId): CustomLayout
    {
        try {
            $customLayout = new CustomLayout();
            $customLayout->setId(mb_strtolower($classId . $layoutName));
            $customLayout->setName($layoutName);
            $customLayout->setClassId($classId);
            $customLayout->save();

            return $customLayout;
        } catch (Exception $exception) {
            $message = sprintf(
                'Custom Layout "%s" for classId "%s" could not be created.',
                $layoutName,
                $classId
            );

            throw new InvalidSettingException(
                $message,
                0,
                $exception
            );
        }
    }

    /** @throws Exception */
    public function delete(string $layoutName, string $classId): void
    {
        $customLayout = $this->find($layoutName, $classId);

        if ($customLayout === null) {
            $message = sprintf(
                'Custom Layout with name "%s" for classId "%s" can not be deleted, because it does not exist.',
                $layoutName,
                $classId
            );
            $this->getOutput()->writeMessage($message);

            return;
        }

        $customLayout->delete();

        $this->forgetRuntimeCache();
    }

    /**
     * getByNameAndClassId() copies the values of a listing item into a fresh model whose DAO never
     * learned where the data lives, so save() and delete() on it silently do nothing. Reloading by
     * id goes through the DAO and records the data source.
     */
    private function find(string $layoutName, string $classId): ?CustomLayout
    {
        $this->forgetRuntimeCache();

        $customLayout = CustomLayout::getByNameAndClassId($layoutName, $classId);

        return $customLayout === null ? null : CustomLayout::getById((string) $customLayout->getId());
    }

    /** @return array<string, mixed> */
    protected function decodeJson(string $json): array
    {
        return $this->serializer->decode($json, 'json', ['json_decode_associative' => true]);
    }

    public function getJsonDefinitionPathForUpMigration(string $layoutName, string $classId): string
    {
        return $this->getJsonFileNameFor($layoutName, $classId, self::UP);
    }

    public function getJsonDefinitionPathForDownMigration(string $layoutName, string $classId): string
    {
        return $this->getJsonFileNameFor($layoutName, $classId, self::DOWN);
    }

    private function getJsonFileNameFor(string $layoutName, string $classId, string $direction): string
    {
        $dataFolder = $direction === self::DOWN ? $this->dataFolder . '/down/' : $this->dataFolder . '/';
        $dataFolder .= $classId . '/custom_definition_' . $layoutName . '_export.json';

        return $dataFolder;
    }
}
