<?php

// ensure we get report on all possible php errors
error_reporting(E_ALL);

(function (): void {
    $composerAutoload = __DIR__ . '/../vendor/autoload.php';

    if (!is_file($composerAutoload)) {
        die('You need to set up the project dependencies using Composer');
    }

    require_once $composerAutoload;
})();
