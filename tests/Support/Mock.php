<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Support;

use PHPUnit\Framework\TestCase;
use Yiisoft\Cache\ArrayCache;
use Yiisoft\Cache\Cache;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Query\Helper\QueryHelper;

final class Mock extends TestCase
{
    private CacheInterface|null $cache = null;
    private SchemaCache|null $schemaCache = null;

    public function cache(): CacheInterface
    {
        if ($this->cache === null) {
            $this->cache = new Cache(new ArrayCache());
        }

        return $this->cache;
    }

    public function connection(): ConnectionInterface
    {
        return $this->createMock(ConnectionInterface::class);
    }

    public static function queryHelper(): QueryHelper
    {
        return new QueryHelper();
    }

    public function schemaCache(): SchemaCache
    {
        if ($this->schemaCache === null) {
            $this->schemaCache = new SchemaCache($this->cache());
        }

        return $this->schemaCache;
    }
}
