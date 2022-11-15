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
        $result = match ($db->getName()) {
            'sqlsrv' => $db->createCommand(
                <<<SQL
                SELECT *
                FROM fn_listextendedproperty (
                    N'MS_description',
                    'SCHEMA', N'dbo',
                    'TABLE', N{$db->getQuoter()->quoteValue($table)},
                    'COLUMN', N{$db->getQuoter()->quoteValue($column)}
                )
                SQL
            )->queryAll(),
        };

        return $result[0]['value'] ?? [];
    }

    public static function getCommmentsFromTable(
        string $table,
        ConnectionPDOInterface $db
    ): array|string {
        $result = match ($db->getName()) {
            'sqlsrv' => $db->createCommand(
                <<<SQL
                SELECT *
                FROM fn_listextendedproperty (
                    N'MS_description',
                    'SCHEMA', N'dbo',
                    'TABLE', N{$db->getQuoter()->quoteValue($table)},
                    DEFAULT, DEFAULT
                )
                SQL
            )->queryAll(),
        };

        return $result[0]['value'] ?? [];
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
