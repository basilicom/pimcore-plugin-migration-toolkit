<?php

declare(strict_types=1);

namespace Basilicom\PimcorePluginMigrationToolkit\Helper;

use Basilicom\PimcorePluginMigrationToolkit\OutputWriter\NullOutputWriter;
use Basilicom\PimcorePluginMigrationToolkit\OutputWriter\OutputWriterInterface;
use Pimcore\Tool;

abstract class AbstractMigrationHelper
{
    public const string UP   = 'up';
    public const string DOWN = 'down';

    protected ?OutputWriterInterface $output = null;

    public function setOutput(OutputWriterInterface $output): void
    {
        $this->output = $output;
    }

    protected function getOutput(): OutputWriterInterface
    {
        return $this->output ?? new NullOutputWriter();
    }

    protected function isValidLanguage(string $language): bool
    {
        return in_array($language, Tool::getValidLanguages());
    }
}
