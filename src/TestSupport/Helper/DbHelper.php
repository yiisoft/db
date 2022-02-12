<?php

declare(strict_types=1);

namespace Yiisoft\Db\TestSupport\Helper;

final class DbHelper
{
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
