<?php

declare(strict_types=1);

namespace Basilicom\PimcorePluginMigrationToolkit\Tests\Functional\Command;

use Basilicom\PimcorePluginMigrationToolkit\Command\MigrateInSeparateProcessesCommand;
use Basilicom\PimcorePluginMigrationToolkit\Tests\App\Migrations\Version20260101000000;
use Basilicom\PimcorePluginMigrationToolkit\Tests\Functional\AbstractFunctionalTestCase;
use Pimcore;
use Pimcore\Db;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Tester\CommandTester;

class MigrateInSeparateProcessesCommandTest extends AbstractFunctionalTestCase
{
    private const string PREFIX = 'Basilicom\PimcorePluginMigrationToolkit\Tests';

    protected function setUp(): void
    {
        parent::setUp();
        $this->revertFixture();
        $this->onTearDown(fn () => $this->revertFixture());
    }

    public function testDryRunListsWithoutExecuting(): void
    {
        $tester = $this->runCommand(['--dry-run' => true, '--bundle' => self::PREFIX]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('1 migration(s) will be executed', $tester->getDisplay());
        self::assertStringContainsString(Version20260101000000::class, $tester->getDisplay());
        self::assertFalse($this->tableExists());
    }

    public function testPrefixFilterCanLeaveNothingToDo(): void
    {
        $tester = $this->runCommand(['--bundle' => 'NoSuchNamespace']);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('No migrations to execute', $tester->getDisplay());
    }

    public function testExecutesEachPendingMigrationInItsOwnProcessAndRevertsOnRequest(): void
    {
        $tester = $this->runCommand(['--bundle' => self::PREFIX, '--timeout' => '0']);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('Executing ' . Version20260101000000::class, $tester->getDisplay());
        self::assertStringContainsString('Migrations finished', $tester->getDisplay());
        self::assertTrue($this->tableExists());

        $tester = $this->runCommand(['--bundle' => self::PREFIX, '--down' => MigrateInSeparateProcessesCommand::DOWN_PREVIOUS]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('Reverting ' . Version20260101000000::class, $tester->getDisplay());
        self::assertFalse($this->tableExists());
    }

    public function testRevertingDownToAVersionIncludesIt(): void
    {
        $this->runCommand(['--bundle' => self::PREFIX]);
        self::assertTrue($this->tableExists());

        $tester = $this->runCommand(['--down' => Version20260101000000::class]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertFalse($this->tableExists());
    }

    public function testRevertingAnUnknownVersionIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('has not been executed');

        $this->runCommand(['--down' => 'App\Migrations\Version19700101000000']);
    }

    /** @param array<string, mixed> $input */
    private function runCommand(array $input): CommandTester
    {
        $command = Pimcore::getContainer()?->get(MigrateInSeparateProcessesCommand::class);
        self::assertInstanceOf(MigrateInSeparateProcessesCommand::class, $command);

        $tester = new CommandTester($command);
        $tester->execute($input);

        return $tester;
    }

    private function tableExists(): bool
    {
        return Db::get()->createSchemaManager()->tablesExist([Version20260101000000::TABLE]);
    }

    private function revertFixture(): void
    {
        if ($this->tableExists()) {
            $this->runCommand(['--down' => Version20260101000000::class]);
        }
    }
}
