<?php

declare(strict_types=1);

namespace Basilicom\PimcorePluginMigrationToolkit\DependencyInjection;

use Pimcore\Model\Translation;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public const string ROOT                    = 'pimcore_plugin_migration_toolkit';
    public const string PARAMETER_CATALOGUE_DIR = self::ROOT . '.translations.catalogue_dir';
    public const string PARAMETER_DOMAINS       = self::ROOT . '.translations.domains';

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder(self::ROOT);

        $treeBuilder->getRootNode()
            ->children()
                ->arrayNode('translations')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('catalogue_dir')
                            ->info('Directory holding the Symfony catalogues <domain>.<locale>.yaml')
                            ->defaultValue('%kernel.project_dir%/translations')
                        ->end()
                        ->arrayNode('domains')
                            ->info('Translation domains synced by basilicom:translations:sync; add "admin" when the classic admin UI bundle is installed')
                            ->scalarPrototype()->end()
                            ->defaultValue([Translation::DOMAIN_DEFAULT])
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
