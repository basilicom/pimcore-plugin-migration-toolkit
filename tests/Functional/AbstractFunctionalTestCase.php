<?php

declare(strict_types=1);

namespace Basilicom\PimcorePluginMigrationToolkit\Tests\Functional;

use Basilicom\PimcorePluginMigrationToolkit\Helper\AbstractMigrationHelper;
use Basilicom\PimcorePluginMigrationToolkit\OutputWriter\CallbackOutputWriter;
use PHPUnit\Framework\TestCase;
use Throwable;

/**
 * Functional tests run against the Pimcore instance installed into tests/App by
 * docker/install.php; the kernel is booted once in tests/bootstrap.php. Every test
 * creates uniquely named elements and registers their removal with onTearDown().
 */
abstract class AbstractFunctionalTestCase extends TestCase
{
    /** @var array<callable(): mixed> */
    private array $cleanups = [];

    /** @var array<string> Messages the helpers wrote through their output writer */
    protected array $messages = [];

    protected function tearDown(): void
    {
        foreach (array_reverse($this->cleanups) as $cleanup) {
            try {
                $cleanup();
            } catch (Throwable) {
            }
        }
        $this->cleanups = [];
        $this->messages = [];

        parent::tearDown();
    }

    /** @param callable(): mixed $cleanup */
    protected function onTearDown(callable $cleanup): void
    {
        $this->cleanups[] = $cleanup;
    }

    /** Alphanumeric only, so it also fits ids that reject punctuation (QuantityValue units). */
    protected function uniqueName(string $prefix): string
    {
        return $prefix . bin2hex(random_bytes(4));
    }

    /**
     * @template T of AbstractMigrationHelper
     *
     * @param T $helper
     *
     * @return T
     */
    protected function withOutput(AbstractMigrationHelper $helper): AbstractMigrationHelper
    {
        $helper->setOutput(new CallbackOutputWriter(function (string $message): void {
            $this->messages[] = $message;
        }));

        return $helper;
    }

    protected function assertMessageContains(string $needle): void
    {
        self::assertNotEmpty($this->messages, 'expected the helper to write a message');
        self::assertStringContainsString($needle, implode(PHP_EOL, $this->messages));
    }
}
