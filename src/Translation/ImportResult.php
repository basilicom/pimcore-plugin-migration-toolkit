<?php

declare(strict_types=1);

namespace Basilicom\PimcorePluginMigrationToolkit\Translation;

final readonly class ImportResult
{
    /** @param array<string> $skippedLocales */
    public function __construct(
        public string $domain,
        public int $createdKeys,
        public int $addedLabels,
        public int $replacedLabels,
        public array $skippedLocales,
    ) {
    }

    public function summary(): string
    {
        $summary = sprintf(
            '[%s] created %d key(s), added %d label(s), replaced %d label(s)',
            $this->domain,
            $this->createdKeys,
            $this->addedLabels,
            $this->replacedLabels,
        );

        if ($this->skippedLocales !== []) {
            $summary .= sprintf(
                '; skipped locale(s) not configured for this domain: %s',
                implode(', ', $this->skippedLocales),
            );
        }

        return $summary;
    }
}
