<?php

namespace Basilicom\PimcorePluginMigrationToolkit\Trait;

use Pimcore\Cache;
use Pimcore\Cache\RuntimeCache;

trait ClearCacheTrait
{
    protected function clearCache(): void
    {
        Cache::clearAll();
        RuntimeCache::clear();
    }
}
