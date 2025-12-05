<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Support;

use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Tests\Support\Stub\StubConnection;
use Yiisoft\Db\Tests\Support\Stub\StubPdoDriver;
use Yiisoft\Test\Support\SimpleCache\MemorySimpleCache;

final class TestHelper
{
    public static function createSqliteMemoryConnection(): StubConnection
    {
        return new StubConnection(
            new StubPdoDriver('sqlite::memory:'),
            self::createMemorySchemaCache(),
        );
    }

    public static function createMemorySchemaCache(): SchemaCache
    {
        return new SchemaCache(
            new MemorySimpleCache(),
        );
    }
}
