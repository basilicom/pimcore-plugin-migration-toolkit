<?php

declare(strict_types=1);

namespace Basilicom\PimcorePluginMigrationToolkit\Tests\Functional\Helper;

use Basilicom\PimcorePluginMigrationToolkit\Exceptions\InvalidSettingException;
use Basilicom\PimcorePluginMigrationToolkit\Helper\ClassDefinitionMigrationHelper;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Service;

class ClassDefinitionMigrationHelperTest extends AbstractDefinitionHelperTestCase
{
    private ClassDefinitionMigrationHelper $helper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->helper = $this->withOutput(new ClassDefinitionMigrationHelper($this->dataFolder));
    }

    public function testCreateOrUpdateImportsAndThenUpdatesTheDefinition(): void
    {
        $name = $this->className();
        $this->writeJson($this->helper->getJsonDefinitionPathForUpMigration($name), $this->exportJson($name, ['title']));

        $this->helper->createOrUpdate($name, $this->helper->getJsonDefinitionPathForUpMigration($name));

        $class = ClassDefinition::getByName($name);
        self::assertNotNull($class);
        self::assertNotNull($class->getFieldDefinition('title'));
        self::assertNull($class->getFieldDefinition('subtitle'));

        $this->writeJson($this->helper->getJsonDefinitionPathForDownMigration($name), $this->exportJson($name, ['title', 'subtitle']));
        $this->helper->createOrUpdate($name, $this->helper->getJsonDefinitionPathForDownMigration($name));

        self::assertNotNull(ClassDefinition::getByName($name)?->getFieldDefinition('subtitle'));
    }

    public function testMissingJsonIsRejected(): void
    {
        $this->expectException(InvalidSettingException::class);

        $this->helper->createOrUpdate($this->className(), $this->dataFolder . '/missing.json');
    }

    public function testDelete(): void
    {
        $name = $this->className();
        $this->writeJson($this->helper->getJsonDefinitionPathForUpMigration($name), $this->exportJson($name, ['title']));
        $this->helper->createOrUpdate($name, $this->helper->getJsonDefinitionPathForUpMigration($name));

        $this->helper->delete($name);

        self::assertNull(ClassDefinition::getByName($name));
    }

    public function testDeleteOfMissingClassIsReported(): void
    {
        $this->helper->delete($this->uniqueName('ToolkitMissing'));

        $this->assertMessageContains('does not exist');
    }

    private function className(): string
    {
        $name = $this->uniqueName('ToolkitClass');
        $this->onTearDown(static fn () => ClassDefinition::getByName($name)?->delete());

        return $name;
    }

    /** @param array<string> $fields */
    private function exportJson(string $name, array $fields): string
    {
        $class = new ClassDefinition();
        $class->setId(strtoupper(substr($name, -8)));
        $class->setName($name);
        $class->setLayoutDefinitions($this->panelWithInputs($fields));

        return Service::generateClassDefinitionJson($class);
    }
}
