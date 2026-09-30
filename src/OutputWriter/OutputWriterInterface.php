<?php

declare(strict_types=1);

namespace Basilicom\PimcorePluginMigrationToolkit\OutputWriter;

interface OutputWriterInterface
{
    public function writeMessage(string $message): void;
}
