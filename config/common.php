<?php

return [
    \yii\db\ConnectionInterface::class => \yii\di\Reference::to('db'),
    'db'                               => [
        '__class'   => \yii\db\Connection::class,
        'dsn'       => 'pgsql:dbname='.$params['db.name']
                        .(!empty($params['db.host']) ? (';host='.$params['db.host']) : '')
                        .(!empty($params['db.port']) ? (';port='.$params['db.port']) : ''),
        'username'  => $params['db.user'],
        'password'  => $params['db.password'],
    ],
];
