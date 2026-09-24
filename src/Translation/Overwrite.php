<?php

declare(strict_types=1);

namespace Basilicom\PimcorePluginMigrationToolkit\Translation;

enum Overwrite: string
{
    case Never  = 'never';
    case Always = 'always';

    /** @return array<string> */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }
}
