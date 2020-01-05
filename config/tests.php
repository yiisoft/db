<?php
declare(strict_types=1);

use Yiisoft\Aliases\Aliases;
use Yiisoft\Db\Connection;

return [
    Aliases::class => [
        '@root' => dirname(__DIR__, 1),
        '@runtime' => '@root/tests/data/runtime',
    ],

    Connection::class => [
        '__class'   => \Yiisoft\Db\Connection::class,
        '__construct()' => [
            'dsn' => $params['database']['dsn']
        ],
        'setUsername()' => [$params['database']['username']],
        'setPassword()' => [$params['database']['password']],
    ],
];
