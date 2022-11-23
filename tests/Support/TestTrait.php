<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Support;

use Yiisoft\Db\Driver\PDO\ConnectionPDOInterface;
use Yiisoft\Db\Tests\Support\DbHelper;
use Yiisoft\Db\Tests\Support\Stub\PDODriver;

trait TestTrait
{
    private CacheInterface|null $cache = null;
    private QueryCache|null $queryCache = null;
    private SchemaCache|null $schemaCache = null;

    protected function getConnection(string $fixture = '', string $dsn = 'sqlite::memory:'): ConnectionPDOInterface
    {
        $db = new Stub\Connection(new PDODriver($dsn), DbHelper::getQueryCache(), DbHelper::getSchemaCache());

        if ($fixture !== '') {
            DbHelper::loadFixture($db, __DIR__ . "/Fixture/$fixture.sql");
        }

        return $db;
    }
}
