<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Debug\ConnectionInterfaceProxy;
use Yiisoft\Db\Debug\DatabaseCollector;

return [
    'yiisoft/yii-debug' => [
        'collectors' => [
            DatabaseCollector::class,
        ],
        'trackedServices' => [
            ConnectionInterface::class => [ConnectionInterfaceProxy::class, DatabaseCollector::class],
        ],
    ],
];
