<?php

declare(strict_types=1);

namespace Basilicom\PimcorePluginMigrationToolkit\Tests\Unit\Translation\Reader;

use Basilicom\PimcorePluginMigrationToolkit\Translation\Exception\InvalidTranslationFileException;
use Basilicom\PimcorePluginMigrationToolkit\Translation\Reader\CsvTranslationReader;
use PHPUnit\Framework\TestCase;

class CsvTranslationReaderTest extends TestCase
{
    private CsvTranslationReader $reader;

    /** @var array<string> */
    private array $files = [];

    protected function setUp(): void
    {
        $this->reader = new CsvTranslationReader();
    }

    protected function tearDown(): void
    {
        foreach ($this->files as $file) {
            @unlink($file);
        }
    }

    public function testReadsThePimcoreExportFormat(): void
    {
        $file = $this->csv("key;de;en;creationDate;modificationDate\ngreeting;Hallo;Hello;0;0\nfarewell;Tschüss;;0;0\n");

        $rows = iterator_to_array($this->reader->read($file));

        self::assertSame([
            'greeting' => ['de' => 'Hallo', 'en' => 'Hello'],
            'farewell' => ['de' => 'Tschüss'],
        ], $rows);
    }

    public function testStripsTheByteOrderMarkFromTheHeader(): void
    {
        $file = $this->csv("\xEF\xBB\xBFkey;en\nbom;Value\n");

        self::assertSame(['bom' => ['en' => 'Value']], iterator_to_array($this->reader->read($file)));
    }

    public function testDelimiterOverridesTheDetectedDialect(): void
    {
        $file = $this->csv("key,en\ncomma,Value\n");

        self::assertSame(['comma' => ['en' => 'Value']], iterator_to_array($this->reader->read($file, ',')));
    }

    public function testUnescapesQuotesAndFormulaGuards(): void
    {
        $file = $this->csv("key;en\nquoted;\"Say \"\"hi\"\"\"\nentity;\"A &quot;B&quot;\"\nformula;'=SUM(A1)\n");

        $rows = iterator_to_array($this->reader->read($file));

        self::assertSame('Say "hi"', $rows['quoted']['en']);
        self::assertSame('A "B"', $rows['entity']['en']);
        self::assertSame('=SUM(A1)', $rows['formula']['en']);
    }

    public function testSkipsRowsWithoutKey(): void
    {
        $file = $this->csv("key;en\n;orphan\nkept;Value\n");

        self::assertSame(['kept' => ['en' => 'Value']], iterator_to_array($this->reader->read($file)));
    }

    public function testMissingKeyColumnIsRejected(): void
    {
        $file = $this->csv("id;en\n1;Value\n");

        $this->expectException(InvalidTranslationFileException::class);
        $this->expectExceptionMessage('Required column "key" is missing.');

        iterator_to_array($this->reader->read($file));
    }

    public function testMissingFileIsRejected(): void
    {
        $this->expectException(InvalidTranslationFileException::class);

        iterator_to_array($this->reader->read(sys_get_temp_dir() . '/does-not-exist.csv'));
    }

    private function csv(string $content): string
    {
        $file = tempnam(sys_get_temp_dir(), 'toolkit-csv-');
        file_put_contents($file, $content);
        $this->files[] = $file;

        return $file;
    }
}
