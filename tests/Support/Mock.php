<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Support;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Yiisoft\Cache\ArrayCache;
use Yiisoft\Cache\Cache;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Db\Cache\QueryCache;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Driver\PDO\ConnectionPDOInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Tests\Support\Stubs\Connection;
use Yiisoft\Log\Logger;
use Yiisoft\Profiler\Profiler;
use Yiisoft\Profiler\ProfilerInterface;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class Mock extends TestCase
{
    private static Cache|null $cache = null;
    private static Logger|null $logger = null;
    private static Profiler|null $profiler = null;
    private static QueryCache|null $queryCache = null;
    private static SchemaCache|null $schemaCache = null;

    public static function getConnection(
        bool $prepareDatabase = false,
        string $dsn = 'sqlite::memory:'
    ): ConnectionPDOInterface {
        $db = new Connection($dsn);

        if ($prepareDatabase) {
            self::prepareDatabase($db, __DIR__ . '/Fixture/sqlite.sql');
        }

        return $db;
    }

    public static function getCache(): CacheInterface
    {
        return self::cache();
    }

    public static function getLogger(): LoggerInterface
    {
        return self::logger();
    }

    public static function getProfiler(): ProfilerInterface
    {
        return self::profiler();
    }

    public static function getQueryCache(): QueryCache
    {
        return self::queryCache();
    }

    public static function getSchemaCache(): SchemaCache
    {
        return self::schemaCache();
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
    public static function prepareDatabase(ConnectionPDOInterface $db, string $fixture): void
    {
        $db->open();
        $lines = explode(';', file_get_contents($fixture));

        foreach ($lines as $line) {
            if (trim($line) !== '') {
                $db->getPDO()?->exec($line);
            }
        }
    }

    private static function cache(): CacheInterface
    {
        if (self::$cache === null) {
            self::$cache = new Cache(new ArrayCache());
        }

        return self::$cache;
    }

    private static function logger(): LoggerInterface
    {
        if (self::$logger === null) {
            self::$logger = new Logger();
        }

        return self::$logger;
    }

    private static function profiler(): ProfilerInterface
    {
        if (self::$profiler === null) {
            self::$profiler = new Profiler(self::logger());
        }

        return self::$profiler;
    }

    private static function queryCache(): QueryCache
    {
        if (self::$queryCache === null) {
            self::$queryCache = new QueryCache(self::cache());
        }

        return self::$queryCache;
    }

    private static function schemaCache(): SchemaCache
    {
        if (self::$schemaCache === null) {
            self::$schemaCache = new SchemaCache(self::cache());
        }

        return self::$schemaCache;
    }
}
