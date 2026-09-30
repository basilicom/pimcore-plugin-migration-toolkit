<?php

declare(strict_types=1);

namespace Basilicom\PimcorePluginMigrationToolkit\OutputWriter;

class NullOutputWriter implements OutputWriterInterface
{
    public function writeMessage(string $message): void
    {
    }
}
