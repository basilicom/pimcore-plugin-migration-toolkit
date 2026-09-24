<?php

declare(strict_types=1);

namespace Basilicom\PimcorePluginMigrationToolkit\Tests\Functional\Helper;

use Basilicom\PimcorePluginMigrationToolkit\Exceptions\InvalidSettingException;
use Basilicom\PimcorePluginMigrationToolkit\Helper\CustomLayoutMigrationHelper;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\CustomLayout;
use Pimcore\Model\DataObject\ClassDefinition\Service;
use Symfony\Component\Serializer\Encoder\JsonEncoder;

class CustomLayoutMigrationHelperTest extends AbstractDefinitionHelperTestCase
{
    private CustomLayoutMigrationHelper $helper;
    private string $classId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->helper = $this->withOutput(new CustomLayoutMigrationHelper($this->dataFolder, new JsonEncoder()));

        $className     = $this->uniqueName('ToolkitLayoutClass');
        $this->classId = strtoupper(substr($className, -8));
        $class         = ClassDefinition::create(['id' => $this->classId, 'name' => $className, 'userOwner' => 0]);
        $class->setLayoutDefinitions($this->panelWithInputs(['title']));
        $class->save();
        $this->onTearDown(static fn () => ClassDefinition::getByName($className)?->delete());
    }

    public function testCreateOrUpdateImportsAndThenUpdatesTheLayout(): void
    {
        $name = $this->layoutName();
        $this->writeJson($this->helper->getJsonDefinitionPathForUpMigration($name, $this->classId), $this->exportJson('first'));

        $this->helper->createOrUpdate($name, $this->classId, $this->helper->getJsonDefinitionPathForUpMigration($name, $this->classId));

        $layout = CustomLayout::getByNameAndClassId($name, $this->classId);
        self::assertNotNull($layout);
        self::assertSame(mb_strtolower($this->classId . $name), $layout->getId());
        self::assertSame('first', $layout->getDescription());
        self::assertNotNull($layout->getFieldDefinition('title'));

        $this->writeJson($this->helper->getJsonDefinitionPathForDownMigration($name, $this->classId), $this->exportJson('second'));
        $this->helper->createOrUpdate($name, $this->classId, $this->helper->getJsonDefinitionPathForDownMigration($name, $this->classId));

        self::assertSame('second', CustomLayout::getByNameAndClassId($name, $this->classId)?->getDescription());
    }

    public function testMissingJsonIsRejected(): void
    {
        $this->expectException(InvalidSettingException::class);

        $this->helper->createOrUpdate($this->layoutName(), $this->classId, $this->dataFolder . '/missing.json');
    }

    public function testDelete(): void
    {
        $name = $this->layoutName();
        $this->writeJson($this->helper->getJsonDefinitionPathForUpMigration($name, $this->classId), $this->exportJson('gone'));
        $this->helper->createOrUpdate($name, $this->classId, $this->helper->getJsonDefinitionPathForUpMigration($name, $this->classId));

        $this->helper->delete($name, $this->classId);

        self::assertNull(CustomLayout::getByNameAndClassId($name, $this->classId));
    }

    public function testDeleteOfMissingLayoutIsReported(): void
    {
        $this->helper->delete($this->uniqueName('missing'), $this->classId);

        $this->assertMessageContains('does not exist');
    }

    private function layoutName(): string
    {
        $name = $this->uniqueName('layout');
        $this->onTearDown(fn () => CustomLayout::getByNameAndClassId($name, $this->classId)?->delete());

        return $name;
    }

    private function exportJson(string $description): string
    {
        $layout = new CustomLayout();
        $layout->setDescription($description);
        $layout->setDefault(false);
        $layout->setLayoutDefinitions($this->panelWithInputs(['title']));

        return Service::generateCustomLayoutJson($layout);
    }
}
