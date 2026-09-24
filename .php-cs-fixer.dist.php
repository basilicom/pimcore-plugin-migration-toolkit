<?php

declare(strict_types=1);

return (new PhpCsFixer\Config())
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->in(['src', 'tests', 'docker'])
            ->exclude(['App/var', 'App/vendor', 'App/public'])
            ->notPath('App/config/reference.php')
            ->append(['.php-cs-fixer.dist.php', 'tests/App/bin/console'])
    )
    ->setRiskyAllowed(true)
    ->setUsingCache(true)
    ->setCacheFile('.php-cs-fixer.cache')
    ->setRules([
        '@PSR12'                 => true,
        'array_indentation'      => true,
        'binary_operator_spaces' => ['operators' => [
            '=>' => 'align_single_space_minimal',
            '='  => 'align_single_space_minimal',
        ]],
        'single_quote'               => true,
        'ordered_imports'            => true,
        'no_superfluous_phpdoc_tags' => true,
        'phpdoc_line_span'           => ['const' => 'single', 'method' => 'single', 'property' => 'single'],
        'no_unused_imports'          => true,
        'declare_strict_types'       => true,
    ]);
