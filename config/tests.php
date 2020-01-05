<?php
declare(strict_types=1);

use \Yiisoft\Db\Connection;

return [
    Connection::class => [
        '__class'   => \Yiisoft\Db\Connection::class,
        '__construct()' => [
            'dsn' => $params['database']['dsn']
        ],
        'setUsername()' => [$params['database']['username']],
        'setPassword()' => [$params['database']['password']],
    ],
];
