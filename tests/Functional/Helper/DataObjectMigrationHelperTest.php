<?php

declare(strict_types=1);

namespace Basilicom\PimcorePluginMigrationToolkit\Tests\Functional\Helper;

use Basilicom\PimcorePluginMigrationToolkit\Exceptions\InvalidSettingException;
use Basilicom\PimcorePluginMigrationToolkit\Helper\DataObjectMigrationHelper;
use Basilicom\PimcorePluginMigrationToolkit\Tests\Functional\AbstractFunctionalTestCase;
use Pimcore\Model\DataObject;

class DataObjectMigrationHelperTest extends AbstractFunctionalTestCase
{
    private DataObjectMigrationHelper $helper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->helper = $this->withOutput(new DataObjectMigrationHelper());
    }

    public function testCreateFolderByPathCreatesTheWholeChain(): void
    {
        $path = $this->folderPath();

        $this->helper->createFolderByPath($path . '/sub');

        self::assertInstanceOf(DataObject\Folder::class, DataObject::getByPath($path));
        self::assertInstanceOf(DataObject\Folder::class, DataObject::getByPath($path . '/sub'));
    }

    public function testCreateFolderByParentId(): void
    {
        $path = $this->folderPath();

        $this->helper->createFolderByParentId(ltrim($path, '/'), 1);

        self::assertInstanceOf(DataObject\Folder::class, DataObject::getByPath($path));
    }

    public function testCreateFolderByParentIdRejectsUnknownParents(): void
    {
        $this->expectException(InvalidSettingException::class);

        $this->helper->createFolderByParentId('orphan', 987654321);
    }

    public function testDeleteByPath(): void
    {
        $path = $this->folderPath();
        $this->helper->createFolderByPath($path);

        $this->helper->deleteByPath($path);

        self::assertNull(DataObject::getByPath($path));
    }

    public function testDeleteById(): void
    {
        $path = $this->folderPath();
        $this->helper->createFolderByPath($path);
        $id = DataObject::getByPath($path)?->getId();
        self::assertNotNull($id);

        $this->helper->deleteById($id);

        self::assertNull(DataObject::getById($id));
    }

    public function testRootAndEmptyPathAreProtected(): void
    {
        try {
            $this->helper->deleteById(1);
            self::fail('root must not be deletable');
        } catch (InvalidSettingException) {
        }

        $this->expectException(InvalidSettingException::class);
        $this->helper->deleteByPath('');
    }

    public function testDeleteOfMissingObjectIsReported(): void
    {
        $this->helper->deleteByPath('/' . $this->uniqueName('toolkit-missing-'));
        $this->helper->deleteById(987654321);

        self::assertCount(2, $this->messages);
        $this->assertMessageContains('does not exist');
    }

    private function folderPath(): string
    {
        $path = '/' . $this->uniqueName('toolkit-objects-');
        $this->onTearDown(static fn () => DataObject::getByPath($path)?->delete());

        return $path;
    }
}
