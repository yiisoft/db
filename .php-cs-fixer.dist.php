<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;
use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;

$finder = (new Finder())->in([
    __DIR__ . '/config',
    __DIR__ . '/src',
    __DIR__ . '/tests',
]);

return (new Config())
    ->setParallelConfig(ParallelConfigFactory::detect())
    ->setRules([
        '@PER-CS3.0' => true,
        'no_unused_imports' => true,
        'ordered_class_elements' => true,
        'class_attributes_separation' => ['elements' => ['method' => 'one']],
        'fully_qualified_strict_types' => [
            'import_symbols' => true
        ],
        'global_namespace_import' => [
            'import_classes' => true,
            'import_constants' => true,
            'import_functions' => true,
        ],
    ])
    ->setFinder($finder);
