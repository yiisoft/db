<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema;

interface QuoterInterface
{
    /**
     * Splits full table name into parts
     *
     * @param string $name
     *
     * @return string[]
     */
    public function getTableNameParts(string $name): array;

    /**
     * Ensures name is wrapped with {{ and }}.
     *
     * @param string $name
     *
     * @return string
     */
    public function ensureNameQuoted(string $name): string;

    /**
     * Ensures name of column is wrapped with [[ and ]].
     *
     * @param string $name
     *
     * @return string
     */
    public function ensureColumnName(string $name): string;

    /**
     * Quotes a column name for use in a query.
     *
     * If the column name contains prefix, the prefix will also be properly quoted. If the column name is already quoted
     * or contains '(', '[[' or '{{', then this method will do nothing.
     *
     * @param string $name column name.
     *
     * @return string the properly quoted column name.
     *
     * {@see quoteSimpleColumnName()}
     */
    public function quoteColumnName(string $name): string;

    /**
     * Quotes a simple column name for use in a query.
     *
     * A simple column name should contain the column name only without any prefix. If the column name is already quoted
     * or is the asterisk character '*', this method will do nothing.
     *
     * @param string $name column name.
     *
     * @return string the properly quoted column name.
     */
    public function quoteSimpleColumnName(string $name): string;

    /**
     * Quotes a simple table name for use in a query.
     *
     * A simple table name should contain the table name only without any schema prefix. If the table name is already
     * quoted, this method will do nothing.
     *
     * @param string $name table name.
     *
     * @return string the properly quoted table name.
     */
    public function quoteSimpleTableName(string $name): string;

    /**
     * Processes a SQL statement by quoting table and column names that are enclosed within double brackets.
     *
     * Tokens enclosed within double curly brackets are treated as table names, while tokens enclosed within double
     * square brackets are column names. They will be quoted accordingly. Also, the percentage character "%" at the
     * beginning or ending of a table name will be replaced with {@see tablePrefix}.
     *
     * @param string $sql the SQL to be quoted
     *
     * @return string the quoted SQL
     */
    public function quoteSql(string $sql): string;

    /**
     * Quotes a table name for use in a query.
     *
     * If the table name contains schema prefix, the prefix will also be properly quoted. If the table name is already
     * quoted or contains '(' or '{{', then this method will do nothing.
     *
     * @param string $name table name.
     *
     * @return string the properly quoted table name.
     *
     * {@see quoteSimpleTableName()}
     */
    public function quoteTableName(string $name): string;

    /**
     * Quotes a string value for use in a query.
     *
     * Note that if the parameter is not a string, it will be returned without change.
     *
     * @param mixed $value
     *
     * @return mixed The properly quoted string.
     */
    public function quoteValue(mixed $value): mixed;

    /**
     * Unquotes a simple column name.
     *
     * A simple column name should contain the column name only without any prefix. If the column name is not quoted or
     * is the asterisk character '*', this method will do nothing.
     *
     * @param string $name column name.
     *
     * @return string unquoted column name.
     */
    public function unquoteSimpleColumnName(string $name): string;

    /**
     * Unquotes a simple table name.
     *
     * A simple table name should contain the table name only without any schema prefix. If the table name is not
     * quoted, this method will do nothing.
     *
     * @param string $name table name.
     *
     * @return string unquoted table name.
     */
    public function unquoteSimpleTableName(string $name): string;
}
