<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Support;

use Psr\Log\LoggerInterface;
use Yiisoft\Cache\Cache;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Cache\File\FileCache;
use Yiisoft\Db\Cache\QueryCache;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Driver\PDO\ConnectionPDOInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Log\Logger;
use Yiisoft\Profiler\Profiler;
use Yiisoft\Profiler\ProfilerInterface;

use function explode;
use function file_get_contents;
use function preg_replace;
use function str_replace;
use function trim;

final class DbHelper
{
    public static function getCache(): CacheInterface
    {
        return new Cache(new FileCache(__DIR__ . '/runtime/cache'));
    }

    public static function getLogger(): LoggerInterface
    {
        return new Logger();
    }

    public static function getQueryCache(): QueryCache
    {
        return new QueryCache(self::getCache());
    }

    public static function getProfiler(): ProfilerInterface
    {
        return new Profiler(self::getLogger());
    }

    public static function getSchemaCache(): SchemaCache
    {
        return new SchemaCache(self::getCache());
    }

    /**
     * Loads the fixture into the database.
     *
     * @throws Exception
     * @throws InvalidConfigException
     */
    public static function loadFixture(ConnectionPDOInterface $db, string $fixture): void
    {
        // flush cache to new import data to dbms.
        self::getCache()->psr()->clear();

        $db->open();

        if ($db->getName() === 'oci') {
            [$drops, $creates] = explode('/* STATEMENTS */', file_get_contents($fixture), 2);
            [$statements, $triggers, $data] = explode('/* TRIGGERS */', $creates, 3);
            $lines = array_merge(
                explode('--', $drops),
                explode(';', $statements),
                explode('/', $triggers),
                explode(';', $data)
            );
        } else {
            $lines = explode(';', file_get_contents($fixture));
        }

        foreach ($lines as $line) {
            if (trim($line) !== '') {
                $db->getPDO()?->exec($line);
            }
        }
    }

    /**
     * Adjust dbms specific escaping.
     *
     * @param string $sql string SQL statement to adjust.
     * @param string $driverName string DBMS name.
     *
     * @return mixed
     */
    public static function replaceQuotes(string $sql, string $driverName): string
    {
        return match ($driverName) {
            'mysql', 'sqlite' => str_replace(['[[', ']]'], '`', $sql),
            'oci' => str_replace(['[[', ']]'], '"', $sql),
            'pgsql' => str_replace(['\\[', '\\]'], ['[', ']'], preg_replace('/(\[\[)|((?<!(\[))]])/', '"', $sql)),
            'db', 'sqlsrv' => str_replace(['[[', ']]'], ['[', ']'], $sql),
            default => $sql,
        };
    }
}
