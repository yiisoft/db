<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Support;

use Psr\SimpleCache\CacheInterface;
use Yiisoft\Cache\File\FileCache;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Driver\Pdo\PdoConnectionInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;

use function explode;
use function file_get_contents;
use function preg_replace;
use function str_replace;
use function trim;

final class DbHelper
{
    public static function changeSqlForOracleBatchInsert(string &$str, array $expectedParams = []): void
    {
        if (empty($str)) {
            return;
        }

        $str = str_replace(
            ' VALUES (',
            "\nSELECT ",
            str_replace(
                '), (',
                " FROM DUAL UNION ALL\nSELECT ",
                substr($str, 0, -1)
            )
        ) . ' FROM DUAL';

        foreach ($expectedParams as $param => $value) {
            $str = match ($value) {
                '1' => preg_replace('/\bTRUE\b/i', $param, $str, 1),
                '0' => preg_replace('/\bFALSE\b/i', $param, $str, 1),
                default => $str,
            };
        }
    }

    public static function getPsrCache(): CacheInterface
    {
        return new FileCache(__DIR__ . '/runtime/cache');
    }

    public static function getSchemaCache(): SchemaCache
    {
        return new SchemaCache(self::getPsrCache());
    }

    /**
     * Loads the fixture into the database.
     *
     * @throws Exception
     * @throws InvalidConfigException
     */
    public static function loadFixture(PdoConnectionInterface $db, string $fixture): void
    {
        // flush cache to new import data to dbms.
        self::getPsrCache()->clear();

        $db->open();

        if ($db->getDriverName() === 'oci') {
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
                $db->getPdo()?->exec($line);
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
            'mysql' => str_replace(['[[', ']]'], '`', $sql),
            'oci', 'sqlite' => str_replace(['[[', ']]'], '"', $sql),
            'pgsql' => str_replace(['\\[', '\\]'], ['[', ']'], preg_replace('/(\[\[)|((?<!(\[))]])/', '"', $sql)),
            'db', 'sqlsrv' => str_replace(['[[', ']]'], ['[', ']'], $sql),
            default => $sql,
        };
    }
}
