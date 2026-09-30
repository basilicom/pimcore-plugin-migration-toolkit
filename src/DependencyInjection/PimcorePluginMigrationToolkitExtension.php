<?php

declare(strict_types=1);

namespace Basilicom\PimcorePluginMigrationToolkit\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class PimcorePluginMigrationToolkitExtension extends Extension
{
    /** @param array<mixed> $configs */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration(new Configuration(), $configs);

        $container->setParameter(Configuration::PARAMETER_CATALOGUE_DIR, $config['translations']['catalogue_dir']);
        $container->setParameter(Configuration::PARAMETER_DOMAINS, $config['translations']['domains']);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');
    }
}
