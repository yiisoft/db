<?php

/**
 * This is the configuration file for the Yii 2 unit tests.
 *
 * You can override configuration values by creating a `config.local.php` file
 * and manipulate the `$config` variable.
 * For example to change MySQL username and password your `config.local.php` should
 * contain the following:
 * ```php
 * <?php
 * $config['databases']['mysql']['username'] = 'yiitest';
 * $config['databases']['mysql']['password'] = 'changeme';
 * ```
 */
$config = [
    'databases' => [
        'mysql' => [
            'dsn' => [
                'driver' => 'mysql',
                'host' => '127.0.0.1',
                'dbname' => 'yiitest',
                'port' => '3306',
            ],
            'fixture' => __DIR__ . '/mysql.sql',
            'username' => 'root',
            'password' => 'root',
        ],
        'sqlite' => [
            'dsn' => 'sqlite:' . __DIR__ . '/yiitest.sq3',
            'fixture' => __DIR__ . '/sqlite.sql',
        ],
        'sqlsrv' => [
            'dsn' => 'sqlsrv:Server=localhost,1433;Database=yiitest',
            'username' => 'SA',
            'password' => 'YourStrong!Passw0rd',
            'fixture' => __DIR__ . '/mssql.sql',
        ],
        'pgsql' => [
            'dsn' => [
                'driver' => 'pgsql',
                'host' => '127.0.0.1',
                'dbname' => 'yiitest',
                'port' => '5432'
            ],
            'username' => 'root',
            'password' => 'root',
            'fixture' => __DIR__ . '/postgres.sql',
        ],
        'oci' => [
            'dsn' => 'oci:dbname=LOCAL_XE;charset=AL32UTF8;',
            'username' => '',
            'password' => '',
            'fixture' => __DIR__ . '/oci.sql',
        ],
    ],
];

if (is_file(__DIR__ . '/config.local.php')) {
    include __DIR__ . '/config.local.php';
}

return $config;
