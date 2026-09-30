<?php

declare(strict_types=1);

namespace Basilicom\PimcorePluginMigrationToolkit\Tests\Unit\Translation;

use Basilicom\PimcorePluginMigrationToolkit\Translation\Overwrite;
use PHPUnit\Framework\TestCase;

class OverwriteTest extends TestCase
{
    public function testValuesListEveryCase(): void
    {
        self::assertSame(['never', 'always'], Overwrite::values());
    }

    public function testCasesResolveFromTheirCliValue(): void
    {
        self::assertSame(Overwrite::Never, Overwrite::from('never'));
        self::assertSame(Overwrite::Always, Overwrite::from('always'));
    }
}
