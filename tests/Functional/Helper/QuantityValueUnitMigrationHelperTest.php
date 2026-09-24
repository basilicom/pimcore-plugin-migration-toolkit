<?php

declare(strict_types=1);

namespace Basilicom\PimcorePluginMigrationToolkit\Tests\Functional\Helper;

use Basilicom\PimcorePluginMigrationToolkit\Exceptions\InvalidSettingException;
use Basilicom\PimcorePluginMigrationToolkit\Helper\QuantityValueUnitMigrationHelper;
use Basilicom\PimcorePluginMigrationToolkit\Tests\Functional\AbstractFunctionalTestCase;
use Pimcore\Model\DataObject\QuantityValue\Unit;

class QuantityValueUnitMigrationHelperTest extends AbstractFunctionalTestCase
{
    private QuantityValueUnitMigrationHelper $helper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->helper = $this->withOutput(new QuantityValueUnitMigrationHelper());
    }

    public function testCreateOrUpdateCreatesAndThenUpdatesTheUnit(): void
    {
        $id = $this->unitId();

        $this->helper->createOrUpdate($id, 'kg', 'Kilogram');
        self::assertSame('Kilogram', Unit::getById($id)?->getLongname());

        $this->helper->createOrUpdate($id, 'kg', 'Kilogramme');
        self::assertSame('Kilogramme', Unit::getById($id)?->getLongname());
        self::assertSame('kg', Unit::getById($id)?->getAbbreviation());
    }

    public function testCreateOrUpdateStoresConversionSettings(): void
    {
        $baseId = $this->unitId();
        $id     = $this->unitId();
        $this->helper->createOrUpdate($baseId, 'm', 'Metre');
        $base = Unit::getById($baseId);
        self::assertNotNull($base);

        $this->helper->createOrUpdate($id, 'km', 'Kilometre', $base, 1000.0, 0.5);

        $unit = Unit::getById($id);
        self::assertSame($baseId, $unit?->getBaseunit()?->getId());
        self::assertSame(1000.0, $unit?->getFactor());
        self::assertSame(0.5, $unit?->getConversionOffset());
    }

    public function testIdsMustBeAlphanumeric(): void
    {
        $this->expectException(InvalidSettingException::class);

        $this->helper->createOrUpdate('not-valid', 'x', 'X');
    }

    public function testDelete(): void
    {
        $id = $this->unitId();
        $this->helper->createOrUpdate($id, 'g', 'Gram');

        $this->helper->delete($id);

        self::assertNull(Unit::getById($id));
    }

    public function testDeleteOfMissingUnitIsReported(): void
    {
        $this->helper->delete($this->uniqueName('toolkitMissing'));

        $this->assertMessageContains('does not exist');
    }

    private function unitId(): string
    {
        $id = $this->uniqueName('tk');
        $this->onTearDown(static fn () => Unit::getById($id)?->delete());

        return $id;
    }
}
