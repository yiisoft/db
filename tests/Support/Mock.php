<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Support;

use PHPUnit\Framework\TestCase;
use Yiisoft\Cache\ArrayCache;
use Yiisoft\Cache\Cache;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Query\QueryInterface;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;
use Yiisoft\Db\Schema\Quoter;
use Yiisoft\Db\Schema\QuoterInterface;
use Yiisoft\Db\Schema\SchemaInterface;
use Yiisoft\Db\Tests\Support\Stubs\QueryBuilder;
use Yiisoft\Db\Tests\Support\Stubs\Schema;

final class Mock extends TestCase
{
    private Cache|null $cache = null;
    private SchemaCache|null $schemaCache = null;

    public function __construct(private string $driverName = '')
    {
    }

    public function connection(): ConnectionInterface
    {
        return $this->createMock(ConnectionInterface::class);
    }

    public function getDriverName(): string
    {
        return $this->driverName;
    }

    public function query(): QueryInterface
    {
        return new Query($this->connection());
    }

    public function queryBuilder(
        array|string $columnQuoteCharacter = '',
        array|string $tableQuoteCharacter = ''
    ): QueryBuilderInterface {
        return new QueryBuilder($this->quoter($columnQuoteCharacter, $tableQuoteCharacter), $this->schema());
    }

    public function quoter(array|string $columnQuoteCharacter, array|string $tableQuoteCharacter): QuoterInterface
    {
        return new Quoter($columnQuoteCharacter, $tableQuoteCharacter);
    }

    public function schema(): SchemaInterface
    {
        return new Schema($this->connection(), $this->schemaCache());
    }

    private function cache(): CacheInterface
    {
        if ($this->cache === null) {
            $this->cache = new Cache(new ArrayCache());
        }

        return $this->cache;
    }

    private function schemaCache(): SchemaCache
    {
        if ($this->schemaCache === null) {
            $this->schemaCache = new SchemaCache($this->cache());
        }

        return $this->schemaCache;
    }
}
