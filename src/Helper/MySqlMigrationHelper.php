<?php

declare(strict_types=1);

namespace Basilicom\PimcorePluginMigrationToolkit\Helper;

use Basilicom\PimcorePluginMigrationToolkit\Exceptions\NotFoundException;

class MySqlMigrationHelper extends AbstractMigrationHelper
{
    protected string $dataFolder;

    public function __construct(string $dataFolder)
    {
        $this->dataFolder = sprintf('%s/sql', $dataFolder);
    }

    /** @throws NotFoundException */
    public function loadSqlFile(string $sqlFile, string $direction = self::UP): string
    {
        $sqlFilePath = $this->getSqlFilePath($sqlFile, $direction);
        if (file_exists($sqlFilePath) === false || is_readable($sqlFilePath) === false) {
            throw new NotFoundException(
                sprintf(
                    'could not find or read file "%s" in migration\'s directory! (used path: %s)',
                    $sqlFile,
                    $sqlFilePath
                )
            );
        }

        return file_get_contents($sqlFilePath);
    }

    private function getSqlFilePath(string $sqlFile, string $direction): string
    {
        return sprintf(
            '%s/%s%s',
            $this->dataFolder,
            $direction === self::DOWN ? 'down/' : '',
            $sqlFile
        );
    }
}
