<?php

declare(strict_types=1);

namespace Basilicom\PimcorePluginMigrationToolkit\Tests\Functional\Helper;

use Basilicom\PimcorePluginMigrationToolkit\Exceptions\InvalidSettingException;
use Basilicom\PimcorePluginMigrationToolkit\Helper\FieldcollectionMigrationHelper;
use Pimcore\Model\DataObject\ClassDefinition\Service;
use Pimcore\Model\DataObject\Fieldcollection\Definition;

class FieldcollectionMigrationHelperTest extends AbstractDefinitionHelperTestCase
{
    private FieldcollectionMigrationHelper $helper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->helper = $this->withOutput(new FieldcollectionMigrationHelper($this->dataFolder));
    }

    public function testCreateOrUpdateImportsAndThenUpdatesTheDefinition(): void
    {
        $key = $this->key();
        $this->writeJson($this->helper->getJsonDefinitionPathForUpMigration($key), $this->exportJson($key, ['title']));

        $this->helper->createOrUpdate($key, $this->helper->getJsonDefinitionPathForUpMigration($key));
        self::assertNotNull(Definition::getByKey($key)?->getFieldDefinition('title'));

        $this->writeJson($this->helper->getJsonDefinitionPathForDownMigration($key), $this->exportJson($key, ['title', 'subtitle']));
        $this->helper->createOrUpdate($key, $this->helper->getJsonDefinitionPathForDownMigration($key));

        self::assertNotNull(Definition::getByKey($key)?->getFieldDefinition('subtitle'));
    }

    public function testMissingJsonIsRejected(): void
    {
        $this->expectException(InvalidSettingException::class);

        $this->helper->createOrUpdate($this->key(), $this->dataFolder . '/missing.json');
    }

    public function testDelete(): void
    {
        $key = $this->key();
        $this->writeJson($this->helper->getJsonDefinitionPathForUpMigration($key), $this->exportJson($key, ['title']));
        $this->helper->createOrUpdate($key, $this->helper->getJsonDefinitionPathForUpMigration($key));

        $this->helper->delete($key);

        self::assertNull(Definition::getByKey($key));
    }

    public function testDeleteOfMissingDefinitionIsReported(): void
    {
        $this->helper->delete($this->uniqueName('ToolkitMissing'));

        $this->assertMessageContains('does not exist');
    }

    private function key(): string
    {
        $key = $this->uniqueName('ToolkitFc');
        $this->onTearDown(static fn () => Definition::getByKey($key)?->delete());

        return $key;
    }

    /** @param array<string> $fields */
    private function exportJson(string $key, array $fields): string
    {
        $definition = new Definition();
        $definition->setKey($key);
        $definition->setLayoutDefinitions($this->panelWithInputs($fields));

        return Service::generateFieldCollectionJson($definition);
    }
}
