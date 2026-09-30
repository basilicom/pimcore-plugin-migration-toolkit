<?php

declare(strict_types=1);

namespace Basilicom\PimcorePluginMigrationToolkit\Tests\Unit\Translation;

use Basilicom\PimcorePluginMigrationToolkit\Translation\ImportResult;
use PHPUnit\Framework\TestCase;

class ImportResultTest extends TestCase
{
    public function testSummaryListsTheCounters(): void
    {
        $result = new ImportResult('messages', 2, 5, 1, []);

        self::assertSame('[messages] created 2 key(s), added 5 label(s), replaced 1 label(s)', $result->summary());
    }

    public function testSummaryNamesSkippedLocales(): void
    {
        $result = new ImportResult('admin', 0, 0, 0, ['fr', 'xx']);

        self::assertStringEndsWith('; skipped locale(s) not configured for this domain: fr, xx', $result->summary());
    }
}
