<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Support;

use Yiisoft\Cache\ArrayCache;
use Yiisoft\Cache\Cache;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Db\Cache\QueryCache;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Driver\PDO\ConnectionPDOInterface;
use Yiisoft\Db\Tests\Support\Stub\PDODriver;

trait TestTrait
{
    private CacheInterface|null $cache = null;
    private QueryCache|null $queryCache = null;
    private SchemaCache|null $schemaCache = null;

    protected function getConnection(string $dsn = 'sqlite::memory:'): ConnectionPDOInterface
    {
        return new Stub\Connection(new PDODriver($dsn), $this->getQueryCache(), $this->getSchemaCache());
    }

    private function getCache(): CacheInterface
    {
        if ($this->cache === null) {
            $this->cache = new Cache(new ArrayCache());
        }

        return $this->cache;
    }

    private function getQueryCache(): QueryCache
    {
        if ($this->queryCache === null) {
            $this->queryCache = new QueryCache($this->getCache());
        }

        return $this->queryCache;
    }

    private function getSchemaCache(): SchemaCache
    {
        if ($this->schemaCache === null) {
            $this->schemaCache = new SchemaCache($this->getCache());
        }

        return $this->schemaCache;
    }
}
