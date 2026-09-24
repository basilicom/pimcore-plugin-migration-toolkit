<?php

declare(strict_types=1);

namespace Basilicom\PimcorePluginMigrationToolkit\Tests\Functional\Helper;

use Basilicom\PimcorePluginMigrationToolkit\Exceptions\InvalidSettingException;
use Basilicom\PimcorePluginMigrationToolkit\Helper\UserRolesMigrationHelper;
use Basilicom\PimcorePluginMigrationToolkit\Tests\Functional\AbstractFunctionalTestCase;
use Pimcore\Model\User\Role;

class UserRolesMigrationHelperTest extends AbstractFunctionalTestCase
{
    private UserRolesMigrationHelper $helper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->helper = $this->withOutput(new UserRolesMigrationHelper());
    }

    public function testCreateStoresPermissionsAndLanguages(): void
    {
        $name = $this->roleName();

        $this->helper->create($name, ['dashboards'], ['page'], ['Product'], ['en', 'xx'], ['de']);

        $role = Role::getByName($name);
        self::assertNotNull($role);
        self::assertSame(['dashboards'], $role->getPermissions());
        self::assertSame(['page'], $role->getDocTypes());
        self::assertSame(['Product'], $role->getClasses());
        self::assertSame(['en'], array_values($role->getWebsiteTranslationLanguagesView()));
        self::assertSame(['de'], array_values($role->getWebsiteTranslationLanguagesEdit()));
        $this->assertMessageContains('xx');
    }

    public function testCreateRejectsDuplicateNames(): void
    {
        $name = $this->roleName();
        $this->helper->create($name);

        $this->expectException(InvalidSettingException::class);

        $this->helper->create($name);
    }

    public function testUpdateReplacesOnlyTheGivenSettings(): void
    {
        $name = $this->roleName();
        $this->helper->create($name, ['dashboards'], ['page']);

        $this->helper->update($name, ['admin_translations']);

        $role = Role::getByName($name);
        self::assertSame(['admin_translations'], $role?->getPermissions());
        self::assertSame(['page'], $role?->getDocTypes());
    }

    public function testUpdateRejectsUnknownRoles(): void
    {
        $this->expectException(InvalidSettingException::class);

        $this->helper->update($this->uniqueName('toolkitMissing'), ['dashboards']);
    }

    public function testDataObjectWorkspaceLifecycle(): void
    {
        $name = $this->roleName();
        $this->helper->create($name);

        $this->helper->addWorkspaceDataObject($name, '/', list: true, view: true, layouts: 'layout1', lEdit: 'en', lView: 'en,de');
        $workspace = Role::getByName($name)?->getWorkspacesObject()[0] ?? null;
        self::assertNotNull($workspace);
        self::assertSame('/', $workspace->getCpath());
        self::assertTrue($workspace->getList());
        self::assertFalse($workspace->getPublish());
        self::assertSame('layout1', $workspace->getLayouts());
        self::assertSame('en,de', $workspace->getLView());

        $this->helper->updateWorkspaceDataObject($name, '/', list: true, view: true, publish: true);
        self::assertTrue(Role::getByName($name)?->getWorkspacesObject()[0]->getPublish());

        $this->helper->deleteWorkspaceDataObject($name, '/');
        self::assertSame([], Role::getByName($name)?->getWorkspacesObject());
    }

    public function testDocumentWorkspaceLifecycle(): void
    {
        $name = $this->roleName();
        $this->helper->create($name);

        $this->helper->addWorkspaceDocument($name, '/', list: true, view: true);
        self::assertTrue(Role::getByName($name)?->getWorkspacesDocument()[0]->getView());

        $this->helper->updateWorkspaceDocument($name, '/', list: true, view: true, publish: true);
        self::assertTrue(Role::getByName($name)?->getWorkspacesDocument()[0]->getPublish());

        $this->helper->deleteWorkspaceDocument($name, '/');
        self::assertSame([], Role::getByName($name)?->getWorkspacesDocument());
    }

    public function testAssetWorkspaceLifecycle(): void
    {
        $name = $this->roleName();
        $this->helper->create($name);

        $this->helper->addWorkspaceAsset($name, '/', list: true, view: true);
        self::assertTrue(Role::getByName($name)?->getWorkspacesAsset()[0]->getList());

        $this->helper->updateWorkspaceAsset($name, '/', list: true, view: true, publish: true);
        self::assertTrue(Role::getByName($name)?->getWorkspacesAsset()[0]->getPublish());

        $this->helper->deleteWorkspaceAsset($name, '/');
        self::assertSame([], Role::getByName($name)?->getWorkspacesAsset());
    }

    public function testWorkspaceForUnknownPathIsRejected(): void
    {
        $name = $this->roleName();
        $this->helper->create($name);

        $this->expectException(InvalidSettingException::class);

        $this->helper->addWorkspaceDataObject($name, '/' . $this->uniqueName('missing-'), list: true);
    }

    public function testUpdateOfUnknownWorkspaceIsRejectedAndDeleteIsReported(): void
    {
        $name = $this->roleName();
        $this->helper->create($name);

        $this->helper->deleteWorkspaceDocument($name, '/');
        $this->assertMessageContains('does not exists');

        $this->expectException(InvalidSettingException::class);
        $this->helper->updateWorkspaceDocument($name, '/', list: true);
    }

    public function testDelete(): void
    {
        $name = $this->roleName();
        $this->helper->create($name);

        $this->helper->delete($name);

        self::assertNull(Role::getByName($name));
    }

    private function roleName(): string
    {
        $name = $this->uniqueName('toolkitRole');
        $this->onTearDown(static fn () => Role::getByName($name)?->delete());

        return $name;
    }
}
