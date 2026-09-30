<?php

declare(strict_types=1);

namespace Basilicom\PimcorePluginMigrationToolkit\Helper;

use Basilicom\PimcorePluginMigrationToolkit\OutputWriter\NullOutputWriter;
use Basilicom\PimcorePluginMigrationToolkit\OutputWriter\OutputWriterInterface;
use Pimcore\Cache\RuntimeCache;
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

    /**
     * Pimcore's config-like models (static routes, translations, website settings, units, definitions)
     * keep a deleted entry in the process-local runtime cache, so a create() later in the same
     * migration would still see it. Only that cache is dropped — never the shared Pimcore cache.
     */
    protected function forgetRuntimeCache(): void
    {
        RuntimeCache::clear();
    }
}
