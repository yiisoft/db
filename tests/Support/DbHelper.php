<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Support;

use Yiisoft\Db\Driver\PDO\ConnectionPDOInterface;

final class DbHelper
{
    public static function getCommmentsFromColumn(
        string $table,
        string $column,
        ConnectionPDOInterface $db
    ): array|string {
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
                    value
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
        ConnectionPDOInterface $db
    ): array|string {
        return match ($db->getName()) {
            'pgsql' => $db->createCommand(
                <<<SQL
                SELECT obj_description(oid, 'pg_class') as description FROM pg_class WHERE relname= :table
                SQL,
                ['table' => $table]
            )->queryOne(),
            'sqlsrv' => $db->createCommand(
                <<<SQL
                SELECT
                    value
                FROM
                    sys.extended_properties
                WHERE
                    major_id = OBJECT_ID(:table) AND minor_id = 0
                SQL,
                ['table' => $table]
            )->queryOne(),
        };
    }

    /**
     * Adjust dbms specific escaping.
     *
     * @param string $sql string SQL statement to adjust.
     * @param string $drivername string DBMS name.
     *
     * @return mixed
     */
    public static function replaceQuotes(string $sql, string $drivername): string
    {
        return match ($drivername) {
            'mysql', 'sqlite' => str_replace(['[[', ']]'], '`', $sql),
            'oci' => str_replace(['[[', ']]'], '"', $sql),
            'pgsql' => str_replace(['\\[', '\\]'], ['[', ']'], preg_replace('/(\[\[)|((?<!(\[))]])/', '"', $sql)),
            'sqlsrv' => str_replace(['[[', ']]'], ['[', ']'], $sql),
            default => $sql,
        };
    }
}
