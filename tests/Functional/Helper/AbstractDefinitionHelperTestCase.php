<?php

declare(strict_types=1);

namespace Basilicom\PimcorePluginMigrationToolkit\Tests\Functional\Helper;

use Basilicom\PimcorePluginMigrationToolkit\Tests\Functional\AbstractFunctionalTestCase;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\ClassDefinition\Layout\Panel;
use Symfony\Component\Filesystem\Filesystem;

/**
 * The definition helpers import Pimcore's JSON export format from a migration's data folder;
 * these tests generate that JSON with Pimcore's own exporter and round-trip it.
 */
abstract class AbstractDefinitionHelperTestCase extends AbstractFunctionalTestCase
{
    protected string $dataFolder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dataFolder = sys_get_temp_dir() . '/toolkit-definitions-' . uniqid('', true);
        (new Filesystem())->mkdir($this->dataFolder);
        $this->onTearDown(fn () => (new Filesystem())->remove($this->dataFolder));
    }

    /** @param array<string> $fieldNames */
    protected function panelWithInputs(array $fieldNames): Panel
    {
        $panel = new Panel();
        $panel->setName('Layout');
        foreach ($fieldNames as $fieldName) {
            $input = new Input();
            $input->setName($fieldName);
            $input->setTitle(ucfirst($fieldName));
            $panel->addChild($input);
        }

        return $panel;
    }

    protected function writeJson(string $path, string $json): void
    {
        (new Filesystem())->mkdir(dirname($path));
        file_put_contents($path, $json);
    }
}
