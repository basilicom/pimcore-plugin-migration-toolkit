<?php

declare(strict_types=1);

namespace Basilicom\PimcorePluginMigrationToolkit\Tests\Functional\Command;

use Basilicom\PimcorePluginMigrationToolkit\Command\MigrateInSeparateProcessesCommand;
use Basilicom\PimcorePluginMigrationToolkit\Tests\Functional\AbstractFunctionalTestCase;
use Pimcore;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class MigrateInSeparateProcessesCommandTest extends AbstractFunctionalTestCase
{
    public function testReportsWhenNothingIsPending(): void
    {
        $tester = $this->runCommand([]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('No migrations to execute', $tester->getDisplay());
    }

    public function testBundleFilterNarrowsTheList(): void
    {
        $tester = $this->runCommand(['--bundle' => 'NoSuchBundle', '--timeout' => '0']);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('No migrations to execute', $tester->getDisplay());
    }

    /** @param array<string, string> $input */
    private function runCommand(array $input): CommandTester
    {
        $command = Pimcore::getContainer()?->get(MigrateInSeparateProcessesCommand::class);
        self::assertInstanceOf(MigrateInSeparateProcessesCommand::class, $command);

        $tester = new CommandTester($command);
        $tester->execute($input);

        return $tester;
    }
}
