<?php

declare(strict_types=1);

namespace Basilicom\PimcorePluginMigrationToolkit\Tests\Functional\Helper;

use Basilicom\PimcorePluginMigrationToolkit\Exceptions\InvalidSettingException;
use Basilicom\PimcorePluginMigrationToolkit\Helper\StaticRoutesMigrationHelper;
use Basilicom\PimcorePluginMigrationToolkit\Tests\Functional\AbstractFunctionalTestCase;
use Pimcore\Bundle\StaticRoutesBundle\Model\Staticroute;

class StaticRoutesMigrationHelperTest extends AbstractFunctionalTestCase
{
    private StaticRoutesMigrationHelper $helper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->helper = $this->withOutput(new StaticRoutesMigrationHelper());
    }

    public function testCreateStoresEveryField(): void
    {
        $name = $this->routeName();

        $this->helper->create($name, '/^\/news\/(.*)$/', '/news/%slug', 'App\Controller\NewsController::detailAction', 'slug', 'slug=home', 7);

        $route = Staticroute::getByName($name);
        self::assertNotNull($route);
        self::assertSame('/^\/news\/(.*)$/', $route->getPattern());
        self::assertSame('/news/%slug', $route->getReverse());
        self::assertSame('App\Controller\NewsController::detailAction', $route->getController());
        self::assertSame('slug', $route->getVariables());
        self::assertSame('slug=home', $route->getDefaults());
        self::assertSame(7, $route->getPriority());
    }

    public function testCreateRejectsDuplicateNames(): void
    {
        $name = $this->routeName();
        $this->helper->create($name, '/^\/a$/', '/a', 'App\Controller\A::index');

        $this->expectException(InvalidSettingException::class);

        $this->helper->create($name, '/^\/b$/', '/b', 'App\Controller\B::index');
    }

    public function testDeleteRemovesTheRoute(): void
    {
        $name = $this->routeName();
        $this->helper->create($name, '/^\/a$/', '/a', 'App\Controller\A::index');

        $this->helper->delete($name);

        self::assertNull(Staticroute::getByName($name));
    }

    public function testDeleteOfMissingRouteIsReported(): void
    {
        $this->helper->delete($this->uniqueName('toolkitMissing'));

        $this->assertMessageContains('does not exist');
    }

    private function routeName(): string
    {
        $name = $this->uniqueName('toolkitRoute');
        $this->onTearDown(static fn () => Staticroute::getByName($name)?->delete());

        return $name;
    }
}
