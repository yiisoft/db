<?php

declare(strict_types=1);

namespace Yiisoft\Db\Driver\Pdo;

class LogTypes
{
    public const KEY = 'log-type';

    public const TYPE_CONNECTION = 'connection';
    public const TYPE_QUERY = 'query';
    public const TYPE_TRANSACTION = 'transaction';
}
