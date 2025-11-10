<?php

namespace Basilicom\PimcorePluginMigrationToolkit\OutputWriter;

interface OutputWriterInterface
{
    public function writeMessage($message): void;
}
