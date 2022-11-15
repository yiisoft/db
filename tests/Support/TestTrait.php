<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Support;

use Psr\Log\LoggerInterface;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Db\Cache\QueryCache;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Driver\PDO\ConnectionPDOInterface;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Schema\Quoter;
use Yiisoft\Db\Schema\QuoterInterface;
use Yiisoft\Profiler\ProfilerInterface;

trait TestTrait
{
    protected function getConnection(): ConnectionPDOInterface
    {
        return Mock::getConnection();
    }

    protected function getConnectionWithData(): ConnectionPDOInterface
    {
        return  Mock::getConnection(true);
    }

    protected function getConnectionWithDsn(string $dsn): ConnectionPDOInterface
    {
        return Mock::getConnection(false, $dsn);
    }

    protected function getCache(): CacheInterface
    {
        return Mock::getCache();
    }

    protected function getQuery(ConnectionPDOInterface $db): Query
    {
        return new Query($db);
    }

    protected function getQuoter(): QuoterInterface
    {
        return new Quoter('`', '`', '');
    }

    protected function getLogger(): LoggerInterface
    {
        return  Mock::getLogger();
    }

    protected function getQueryCache(): QueryCache
    {
        return  Mock::getQueryCache();
    }

    protected function getSchemaCache(): SchemaCache
    {
        return  Mock::getSchemaCache();
    }

    protected function getProfiler(): ProfilerInterface
    {
        return  Mock::getProfiler();
    }
}
