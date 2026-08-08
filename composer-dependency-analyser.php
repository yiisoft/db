<?php

declare(strict_types=1);

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

return (new Configuration())
    ->disableComposerAutoloadPathScan()
    ->setFileExtensions(['php'])
    ->addPathToScan(__DIR__ . '/config', isDev: false)
    ->addPathToScan(__DIR__ . '/src', isDev: false)
    ->addPathToScan(__DIR__ . '/tests', isDev: true)
    ->ignoreErrorsOnPackages(['yiisoft/yii-debug'], [ErrorType::DEV_DEPENDENCY_IN_PROD])
    // Virtual packages that are not directly used in the code.
    ->ignoreErrorsOnPackages(
        ['psr/simple-cache-implementation', 'yiisoft/db-implementation'],
        [ErrorType::UNUSED_DEPENDENCY],
    );
