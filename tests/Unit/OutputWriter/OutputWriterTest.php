<?php

declare(strict_types=1);

namespace Basilicom\PimcorePluginMigrationToolkit\Tests\Unit\OutputWriter;

use Basilicom\PimcorePluginMigrationToolkit\Helper\AbstractMigrationHelper;
use Basilicom\PimcorePluginMigrationToolkit\OutputWriter\CallbackOutputWriter;
use Basilicom\PimcorePluginMigrationToolkit\OutputWriter\NullOutputWriter;
use Basilicom\PimcorePluginMigrationToolkit\OutputWriter\OutputWriterInterface;
use PHPUnit\Framework\TestCase;

class OutputWriterTest extends TestCase
{
    public function testCallbackWriterForwardsEveryMessage(): void
    {
        $messages = [];
        $writer   = new CallbackOutputWriter(function (string $message) use (&$messages): void {
            $messages[] = $message;
        });

        $writer->writeMessage('one');
        $writer->writeMessage('two');

        self::assertSame(['one', 'two'], $messages);
    }

    public function testNullWriterSwallowsMessages(): void
    {
        $writer = new NullOutputWriter();
        $writer->writeMessage('ignored');

        self::assertInstanceOf(OutputWriterInterface::class, $writer);
    }

    public function testHelperFallsBackToTheNullWriterAndAcceptsAnotherOne(): void
    {
        $helper = new class () extends AbstractMigrationHelper {
            public function output(): OutputWriterInterface
            {
                return $this->getOutput();
            }
        };

        self::assertInstanceOf(NullOutputWriter::class, $helper->output());

        $writer = new NullOutputWriter();
        $helper->setOutput($writer);

        self::assertSame($writer, $helper->output());
    }
}
