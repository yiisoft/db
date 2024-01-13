<?php

declare(strict_types=1);

namespace Yiisoft\Db\Driver\Pdo;

final class LogType
{
    public const LOG_TYPE = 'log-type';

    public const CONNECTION = 'connection';
    public const QUERY = 'query';
    public const TRANSACTION = 'transaction';
}
