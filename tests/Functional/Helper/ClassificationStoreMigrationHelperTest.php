<?php

declare(strict_types=1);

namespace Basilicom\PimcorePluginMigrationToolkit\Tests\Functional\Helper;

use Basilicom\PimcorePluginMigrationToolkit\Exceptions\NotFoundException;
use Basilicom\PimcorePluginMigrationToolkit\Helper\ClassificationStoreMigrationHelper;
use Basilicom\PimcorePluginMigrationToolkit\Tests\Functional\AbstractFunctionalTestCase;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;
use Pimcore\Model\DataObject\Classificationstore\StoreConfig;

class ClassificationStoreMigrationHelperTest extends AbstractFunctionalTestCase
{
    private ClassificationStoreMigrationHelper $helper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->helper = $this->withOutput(new ClassificationStoreMigrationHelper());
    }

    public function testStoreLifecycle(): void
    {
        $name = $this->storeName();

        $store = $this->helper->createOrUpdateStore($name, 'first');
        self::assertSame('first', StoreConfig::getByName($name)?->getDescription());

        $this->helper->createOrUpdateStore($name, 'second');
        self::assertSame($store->getId(), $this->helper->getStoreByName($name)->getId());
        self::assertSame('second', $this->helper->getStoreByName($name)->getDescription());

        $this->helper->deleteStore($name);
        self::assertNull(StoreConfig::getByName($name));
    }

    public function testGetStoreByNameRejectsUnknownStores(): void
    {
        $this->expectException(NotFoundException::class);

        $this->helper->getStoreByName($this->uniqueName('ToolkitMissing'));
    }

    public function testGroupLifecycle(): void
    {
        $storeId = (int) $this->helper->createOrUpdateStore($this->storeName(), 'store')->getId();

        $group = $this->helper->createOrUpdateGroup('Group', 'first', $storeId);
        self::assertSame('first', GroupConfig::getByName('Group', $storeId)?->getDescription());

        $this->helper->createOrUpdateGroup('Group', 'second', $storeId);
        self::assertSame($group->getId(), GroupConfig::getByName('Group', $storeId)?->getId());
        self::assertSame('second', GroupConfig::getByName('Group', $storeId)?->getDescription());

        $renamed = $this->helper->renameGroup('Group', 'Renamed', $storeId);
        self::assertSame($group->getId(), $renamed?->getId());
        self::assertNotNull(GroupConfig::getByName('Renamed', $storeId));

        $this->helper->deleteGroup('Renamed', $storeId);
        self::assertNull(GroupConfig::getByName('Renamed', $storeId));
    }

    public function testKeyLifecycle(): void
    {
        $storeId = (int) $this->helper->createOrUpdateStore($this->storeName(), 'store')->getId();
        $group   = $this->helper->createOrUpdateGroup('Group', 'group', $storeId);
        $input   = new Input();
        $input->setName('color');
        $input->setTitle('Color');

        $this->helper->createOrUpdateKey('color', 'Color', 'the color', $input, $storeId, 'Group');

        $key = KeyConfig::getByName('color', $storeId);
        self::assertNotNull($key);
        self::assertSame('input', $key->getType());
        self::assertSame('the color', $key->getDescription());
        self::assertTrue($key->getEnabled());
        self::assertNotNull(KeyGroupRelation::getByGroupAndKeyId((int) $group->getId(), (int) $key->getId()));

        $this->helper->deleteKey('color', $storeId);
        self::assertNull(KeyConfig::getByName('color', $storeId));
    }

    public function testDeletingMissingEntriesIsReported(): void
    {
        $storeId = (int) $this->helper->createOrUpdateStore($this->storeName(), 'store')->getId();

        $this->helper->deleteStore($this->uniqueName('ToolkitMissing'));
        $this->helper->deleteGroup('missing', $storeId);
        $this->helper->deleteKey('missing', $storeId);

        self::assertCount(3, $this->messages);
        $this->assertMessageContains('does not exist');
    }

    private function storeName(): string
    {
        $name = $this->uniqueName('ToolkitStore');
        $this->onTearDown(static function () use ($name): void {
            $store = StoreConfig::getByName($name);
            if ($store === null) {
                return;
            }
            foreach (['color'] as $keyName) {
                KeyConfig::getByName($keyName, (int) $store->getId())?->delete();
            }
            foreach (['Group', 'Renamed'] as $groupName) {
                GroupConfig::getByName($groupName, (int) $store->getId())?->delete();
            }
            $store->delete();
        });

        return $name;
    }
}
