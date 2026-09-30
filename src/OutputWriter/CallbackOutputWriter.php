<?php

declare(strict_types=1);

namespace Basilicom\PimcorePluginMigrationToolkit\OutputWriter;

use Closure;

class CallbackOutputWriter implements OutputWriterInterface
{
    protected Closure $callback;

    public function __construct(Closure $callback)
    {
        $this->callback = $callback;
    }

    public function writeMessage(string $message): void
    {
        ($this->callback)($message);
    }
}
