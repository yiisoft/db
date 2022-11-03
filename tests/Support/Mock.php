<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Support;

use PHPUnit\Framework\TestCase;
use Yiisoft\Cache\ArrayCache;
use Yiisoft\Cache\Cache;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Db\Cache\QueryCache;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Driver\PDO\ConnectionPDOInterface;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Query\QueryInterface;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;
use Yiisoft\Db\Schema\Quoter;
use Yiisoft\Db\Schema\QuoterInterface;
use Yiisoft\Db\Schema\SchemaInterface;
use Yiisoft\Db\Tests\Support\Stubs\Connection;
use Yiisoft\Db\Tests\Support\Stubs\QueryBuilder;
use Yiisoft\Db\Tests\Support\Stubs\Schema;

final class Mock extends TestCase
{
    private Cache|null $cache = null;
    private QueryCache|null $queryCache = null;
    private SchemaCache|null $schemaCache = null;

    public function __construct()
    {
    }

    public function connection(bool $prepareDatabase = false): ConnectionInterface
    {
        $db = new Connection();

        if ($prepareDatabase) {
            $this->prepareDatabase($db);
        }

        return $db;
    }

    public function getDriverName(): string
    {
        return $this->connection()->getDriver()->getDriverName();
    }

    public function getQueryCache(): QueryCache
    {
        return $this->queryCache();
    }

    public function getSchemaCache(): SchemaCache
    {
        return $this->schemaCache();
    }

    public function query(): QueryInterface
    {
        return new Query($this->connection());
    }

    public function queryBuilder(): QueryBuilderInterface {
        return $this->connection()->getQueryBuilder();
    }

    public function quoter(): QuoterInterface
    {
        return $this->connection()->getQuoter();
    }

    public function schema(): SchemaInterface
    {
        return $this->connection()->getSchema();
    }

    private function cache(): CacheInterface
    {
        if ($this->cache === null) {
            $this->cache = new Cache(new ArrayCache());
        }

        return $this->cache;
    }

    private function prepareDatabase(ConnectionPDOInterface $db, string $fixture = __DIR__ . '/Fixture/sqlite.sql'): void
    {
        $db->open();
        $lines = explode(';', file_get_contents($fixture));

        foreach ($lines as $line) {
            if (trim($line) !== '') {
                $db->getPDO()?->exec($line);
            }
        }
    }

    private function queryCache(): QueryCache
    {
        if ($this->queryCache === null) {
            $this->queryCache = new QueryCache($this->cache());
        }
        return $this->queryCache;
    }

    private function schemaCache(): SchemaCache
    {
        if ($this->schemaCache === null) {
            $this->schemaCache = new SchemaCache($this->cache());
        }

        return $this->schemaCache;
    }
}
