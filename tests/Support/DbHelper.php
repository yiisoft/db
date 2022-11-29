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
                    [[TABLE_COMMENT]] as comment
                FROM
                    [[information_schema.TABLES]]
                WHERE
                    [[TABLE_SCHEMA]] = :schema AND [[TABLE_NAME]] = :table AND [[TABLE_COMMENT]] != ''
                SQL,
                ['schema' => $schema, 'table' => $table]
            )->queryOne(),
            'pgsql' => $db->createCommand(
                <<<SQL
                SELECT
                    [[obj_description(oid, 'pg_class')]] as comment
                FROM
                    [[pg_class]]
                WHERE
                    [[relname]] = :table AND [[obj_description(oid, 'pg_class')]] != ''
                SQL,
                ['table' => $table]
            )->queryOne(),
            'sqlsrv' => $db->createCommand(
                <<<SQL
                SELECT
                    [[value]] as comment
                FROM
                    [[sys.extended_properties]]
                WHERE
                    [[major_id]] = OBJECT_ID(:table) AND [[minor_id]] = 0
                SQL,
                ['table' => $table]
            )->queryOne(),
            'oci' => $db->createCommand(
                <<<SQL
                SELECT [[COMMENTS]] AS "comment" FROM [[DBA_TAB_COMMENTS]] WHERE [[TABLE_NAME]] = :tables AND [[COMMENTS]] IS NOT NULL
                SQL,
                ['tables' => $table]
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

        if ($db->getName() !== 'sqlite') {
            $db->close();
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
