<?php

declare(strict_types=1);

namespace Basilicom\PimcorePluginMigrationToolkit\Tests\Unit\Helper;

use Basilicom\PimcorePluginMigrationToolkit\Helper\ClassDefinitionMigrationHelper;
use Basilicom\PimcorePluginMigrationToolkit\Helper\CustomLayoutMigrationHelper;
use Basilicom\PimcorePluginMigrationToolkit\Helper\FieldcollectionMigrationHelper;
use Basilicom\PimcorePluginMigrationToolkit\Helper\ObjectbrickMigrationHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Encoder\JsonEncoder;

class JsonDefinitionPathTest extends TestCase
{
    private const string DATA = '/data/Version1';

    public function testClassDefinitionPaths(): void
    {
        $helper = new ClassDefinitionMigrationHelper(self::DATA);

        self::assertSame(self::DATA . '/class_Product_export.json', $helper->getJsonDefinitionPathForUpMigration('Product'));
        self::assertSame(self::DATA . '/down/class_Product_export.json', $helper->getJsonDefinitionPathForDownMigration('Product'));
    }

    public function testObjectbrickPaths(): void
    {
        $helper = new ObjectbrickMigrationHelper(self::DATA);

        self::assertSame(self::DATA . '/objectbrick_Brick_export.json', $helper->getJsonDefinitionPathForUpMigration('Brick'));
        self::assertSame(self::DATA . '/down/objectbrick_Brick_export.json', $helper->getJsonDefinitionPathForDownMigration('Brick'));
    }

    public function testFieldcollectionPaths(): void
    {
        $helper = new FieldcollectionMigrationHelper(self::DATA);

        self::assertSame(self::DATA . '/fieldcollection_Fc_export.json', $helper->getJsonDefinitionPathForUpMigration('Fc'));
        self::assertSame(self::DATA . '/down/fieldcollection_Fc_export.json', $helper->getJsonDefinitionPathForDownMigration('Fc'));
    }

    public function testCustomLayoutPaths(): void
    {
        $helper = new CustomLayoutMigrationHelper(self::DATA, new JsonEncoder());

        self::assertSame(self::DATA . '/CL/custom_definition_readOnly_export.json', $helper->getJsonDefinitionPathForUpMigration('readOnly', 'CL'));
        self::assertSame(self::DATA . '/down/CL/custom_definition_readOnly_export.json', $helper->getJsonDefinitionPathForDownMigration('readOnly', 'CL'));
    }
}
