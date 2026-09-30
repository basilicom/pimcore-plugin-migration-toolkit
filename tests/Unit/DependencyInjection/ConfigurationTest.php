<?php

declare(strict_types=1);

namespace Basilicom\PimcorePluginMigrationToolkit\Tests\Unit\DependencyInjection;

use Basilicom\PimcorePluginMigrationToolkit\DependencyInjection\Configuration;
use Basilicom\PimcorePluginMigrationToolkit\DependencyInjection\PimcorePluginMigrationToolkitExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ConfigurationTest extends TestCase
{
    public function testDefaultsPointAtTheProjectCatalogues(): void
    {
        $config = (new Processor())->processConfiguration(new Configuration(), []);

        self::assertSame('%kernel.project_dir%/translations', $config['translations']['catalogue_dir']);
        self::assertSame(['messages'], $config['translations']['domains']);
    }

    public function testDomainsAndDirectoryCanBeOverridden(): void
    {
        $config = (new Processor())->processConfiguration(new Configuration(), [
            ['translations' => ['catalogue_dir' => '/catalogues', 'domains' => ['messages', 'custom']]],
        ]);

        self::assertSame('/catalogues', $config['translations']['catalogue_dir']);
        self::assertSame(['messages', 'custom'], $config['translations']['domains']);
    }

    public function testExtensionExposesTheConfigAsParametersAndRegistersTheServices(): void
    {
        $container = new ContainerBuilder();

        (new PimcorePluginMigrationToolkitExtension())->load(
            [['translations' => ['domains' => ['messages']]]],
            $container,
        );

        self::assertSame('%kernel.project_dir%/translations', $container->getParameter(Configuration::PARAMETER_CATALOGUE_DIR));
        self::assertSame(['messages'], $container->getParameter(Configuration::PARAMETER_DOMAINS));
        self::assertTrue($container->hasDefinition('Basilicom\PimcorePluginMigrationToolkit\Command\SyncTranslationsCommand'));
        self::assertTrue($container->hasDefinition('Basilicom\PimcorePluginMigrationToolkit\Translation\TranslationImporter'));
        self::assertFalse($container->hasDefinition('Basilicom\PimcorePluginMigrationToolkit\Translation\Overwrite'));
    }
}
