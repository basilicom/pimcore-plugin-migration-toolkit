<?php

namespace Basilicom\PimcorePluginMigrationToolkit\OutputWriter;

use Closure;

class CallbackOutputWriter implements OutputWriterInterface
{
    protected Closure $callback;

    public function __construct(Closure $callback)
    {
        $this->callback = $callback;
    }

    public function writeMessage($message): void
    {
        ($this->callback)($message);
    }
}
