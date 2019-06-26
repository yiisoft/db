<?php

use Yiisoft\Db\Connection;
use Yiisoft\Db\ConnectionInterface;
use Yiisoft\Factory\Definitions\Reference;

return [
    ConnectionInterface::class => Reference::to('db'),
    'db'                               => [
        '__class'   => Connection::class,
        'dsn'       => 'pgsql:dbname='.$params['db.name']
            .(!empty($params['db.host']) ? (';host='.$params['db.host']) : '')
            .(!empty($params['db.port']) ? (';port='.$params['db.port']) : ''),
        'username'  => $params['db.user'],
        'password'  => $params['db.password'],
    ],
];
