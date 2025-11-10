<?php

namespace Basilicom\PimcorePluginMigrationToolkit\OutputWriter;

class NullOutputWriter implements OutputWriterInterface
{
    public function __construct()
    {
    }

    public function writeMessage($message): void
    {
    }
}
