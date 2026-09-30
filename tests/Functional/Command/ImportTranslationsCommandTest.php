<?php

declare(strict_types=1);

namespace Basilicom\PimcorePluginMigrationToolkit\Tests\Functional\Command;

use Basilicom\PimcorePluginMigrationToolkit\Command\ImportTranslationsCommand;
use Basilicom\PimcorePluginMigrationToolkit\Tests\Functional\AbstractFunctionalTestCase;
use Pimcore;
use Pimcore\Model\Translation;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class ImportTranslationsCommandTest extends AbstractFunctionalTestCase
{
    public function testImportsACsvExportAndReportsTheResult(): void
    {
        $key  = $this->translationKey();
        $file = $this->csv("key;de;en\n{$key};Hallo;Hello\n");

        $tester = $this->runCommand(['file' => $file]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('[messages] created 1 key(s), added 2 label(s)', $tester->getDisplay());
        self::assertSame('Hello', Translation::getByKey($key)?->getTranslation('en'));
    }

    public function testOverwriteAlwaysReplacesExistingLabels(): void
    {
        $key  = $this->translationKey();
        $seed = Translation::getByKey($key, Translation::DOMAIN_DEFAULT, true);
        $seed?->addTranslation('en', 'Old');
        $seed?->save();
        $file = $this->csv("key;en\n{$key};New\n");

        $this->runCommand(['file' => $file]);
        self::assertSame('Old', Translation::getByKey($key)?->getTranslation('en'));

        $tester = $this->runCommand(['file' => $file, '--overwrite' => 'always']);
        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertSame('New', Translation::getByKey($key)?->getTranslation('en'));
    }

    public function testUnknownDomainFails(): void
    {
        $tester = $this->runCommand(['file' => $this->csv("key;en\nk;v\n"), '--domain' => 'nope' . bin2hex(random_bytes(3))]);

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('is not a registered translation domain', $tester->getDisplay());
    }

    public function testInvalidOverwriteModeIsRejected(): void
    {
        $tester = $this->runCommand(['file' => '/dev/null', '--overwrite' => 'sometimes']);

        self::assertSame(Command::INVALID, $tester->getStatusCode());
        self::assertStringContainsString('--overwrite must be one of', $tester->getDisplay());
    }

    public function testMissingFileFails(): void
    {
        $tester = $this->runCommand(['file' => sys_get_temp_dir() . '/missing-' . uniqid() . '.csv']);

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('does not exist or is not readable', $tester->getDisplay());
    }

    /** @param array<string, string> $input */
    private function runCommand(array $input): CommandTester
    {
        $command = Pimcore::getContainer()?->get(ImportTranslationsCommand::class);
        self::assertInstanceOf(ImportTranslationsCommand::class, $command);

        $tester = new CommandTester($command);
        $tester->execute($input);

        return $tester;
    }

    private function translationKey(string $domain = Translation::DOMAIN_DEFAULT): string
    {
        $key = $this->uniqueName('toolkit.import.');
        $this->onTearDown(static fn () => Translation::getByKey($key, $domain)?->delete());

        return $key;
    }

    private function csv(string $content): string
    {
        $file = tempnam(sys_get_temp_dir(), 'toolkit-import-');
        file_put_contents($file, $content);
        $this->onTearDown(static fn () => @unlink($file));

        return $file;
    }
}
