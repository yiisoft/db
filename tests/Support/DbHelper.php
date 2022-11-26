<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Support;

use Yiisoft\Cache\ArrayCache;
use Yiisoft\Cache\Cache;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Db\Cache\QueryCache;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Driver\PDO\ConnectionPDOInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;

use function explode;
use function file_get_contents;
use function preg_replace;
use function str_replace;
use function trim;

final class DbHelper
{
    public static function getCache(): CacheInterface
    {
        return new Cache(new ArrayCache());
    }

    public static function getCommmentsFromColumn(
        string $table,
        string $column,
        ConnectionPDOInterface $db
    ): array|null {
        return match ($db->getName()) {
            'pgsql' => $db->createCommand(
                <<<SQL
                SELECT
                    pgd.description
                FROM
                    pg_catalog.pg_statio_all_tables as st
                INNER JOIN pg_catalog.pg_description pgd ON (pgd.objoid=st.relid)
                INNER JOIN pg_catalog.pg_attribute pga ON (pga.attrelid=st.relid AND pga.attnum=pgd.objsubid)
                WHERE
                    st.relname=:table AND pga.attname=:column
                SQL,
                ['table' => $table, 'column' => $column]
            )->queryOne(),
            'sqlsrv' => $db->createCommand(
                <<<SQL
                SELECT
                    value as comment
                FROM
                    sys.extended_properties
                WHERE
                    major_id = OBJECT_ID(:table) AND minor_id = COLUMNPROPERTY(major_id, :column, 'ColumnId')
                SQL,
                ['table' => $table, 'column' => $column]
            )->queryOne(),
        };
    }

    public static function getCommmentsFromTable(
        string $table,
        ConnectionPDOInterface $db,
        string $schema = 'yiitest'
    ): array|null {
        return match ($db->getName()) {
            'mysql' => $db->createCommand(
                <<<SQL
                SELECT
                    TABLE_COMMENT as comment
                FROM
                    information_schema.TABLES
                WHERE
                    TABLE_SCHEMA = :schema AND TABLE_NAME = :table AND TABLE_COMMENT != ''
                SQL,
                ['schema' => $schema, 'table' => $table]
            )->queryOne(),
            'pgsql' => $db->createCommand(
                <<<SQL
                SELECT obj_description(oid, 'pg_class') as description FROM pg_class WHERE relname= :table
                SQL,
                ['table' => $table]
            )->queryOne(),
            'sqlsrv' => $db->createCommand(
                <<<SQL
                SELECT
                    value as comment
                FROM
                    sys.extended_properties
                WHERE
                    major_id = OBJECT_ID(:table) AND minor_id = 0
                SQL,
                ['table' => $table]
            )->queryOne(),
        };
    }

    public static function getQueryCache(): QueryCache
    {
        return new QueryCache(self::getCache());
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
        $db->open();
        $lines = explode(';', file_get_contents($fixture));

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
