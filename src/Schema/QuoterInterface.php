<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema;

use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Expression\ExpressionInterface;

/**
 * The QuoterInterface class is an interface that provides a set of methods that can be used to quote table and column
 * names, values, and other SQL expressions independently of the database.
 */
interface QuoterInterface
{
    /**
     * Clean up table names and aliases.
     *
     * Both aliases and names are enclosed into {{ and }}.
     *
     * @param array $tableNames non-empty array
     *
     * @throws InvalidArgumentException
     *
     * @psalm-return array<array-key, ExpressionInterface|string> table names indexed by aliases
     */
    public function cleanUpTableNames(array $tableNames): array;

    /**
     * Splits full table name into parts.
     *
     * @param string $name The full name of the table.
     * @param bool $withColumn For cases when full name contain as last prat name of column.
     *
     * @return string[] The table name parts.
     */
    public function getTableNameParts(string $name, bool $withColumn = false): array;

    /**
     * Ensures name is wrapped with {{ and }}.
     *
     * @param string $name The name to be quoted.
     *
     * @return string The quoted name.
     */
    public function ensureNameQuoted(string $name): string;

    /**
     * Ensures name of column is wrapped with [[ and ]].
     *
     * @param string $name The name to be quoted.
     *
     * @return string The quoted name.
     */
    public function ensureColumnName(string $name): string;

    /**
     * Quotes a column name for use in a query.
     *
     * If the column name contains prefix, the prefix will also be properly quoted. If the column name is already quoted
     * or contains '(', '[[' or '{{', then this method will do nothing.
     *
     * @param string $name The column name to be quoted.
     *
     * @return string The properly quoted column name.
     *
     * @see quoteSimpleColumnName()
     */
    public function quoteColumnName(string $name): string;

    /**
     * Quotes a simple column name for use in a query.
     *
     * A simple column name should contain the column name only without any prefix. If the column name is already quoted
     * or is the asterisk character '*', this method will do nothing.
     *
     * @param string $name The column name to be quoted.
     *
     * @return string The properly quoted column name.
     */
    public function quoteSimpleColumnName(string $name): string;

    /**
     * Quotes a simple table name for use in a query.
     *
     * A simple table name should contain the table name only without any schema prefix. If the table name is already
     * quoted, this method will do nothing.
     *
     * @param string $name The table name to be quoted.
     *
     * @return string The properly quoted table name.
     */
    public function quoteSimpleTableName(string $name): string;

    /**
     * Processes a SQL statement by quoting table and column names that are enclosed within double brackets.
     *
     * Tokens enclosed within double curly brackets are treated as table names, while tokens enclosed within double
     * square brackets are column names. They will be quoted accordingly. Also, the percentage character "%" at the
     * beginning or ending of a table name will be replaced with {@see tablePrefix}.
     *
     * @param string $sql The SQL statement to be quoted.
     *
     * @return string The quoted SQL statement.
     */
    public function quoteSql(string $sql): string;

    /**
     * Quotes a table name for use in a query.
     *
     * If the table name contains schema prefix, the prefix will also be properly quoted. If the table name is already
     * quoted or contains '(' or '{{', then this method will do nothing.
     *
     * @param string $name The table name to be quoted.
     *
     * @return string The properly quoted table name.
     *
     * @see quoteSimpleTableName()
     */
    public function quoteTableName(string $name): string;

    /**
     * Quotes a string value for use in a query.
     *
     * Note that if the parameter is not a string, it will be returned without change.
     * Attention: The usage of this method is not safe. Use prepared statements.
     *
     * @param mixed $value The value to be quoted.
     *
     * @return mixed The properly quoted value.
     */
    public function quoteValue(mixed $value): mixed;

    /**
     * Unquotes a simple column name.
     *
     * A simple column name should contain the column name only without any prefix. If the column name is not quoted or
     * is the asterisk character '*', this method will do nothing.
     *
     * @param string $name The column name to be unquoted.
     *
     * @return string The unquoted column name.
     */
    public function unquoteSimpleColumnName(string $name): string;

    /**
     * Unquotes a simple table name.
     *
     * A simple table name should contain the table name only without any schema prefix. If the table name is not
     * quoted, this method will do nothing.
     *
     * @param string $name The table name to be unquoted.
     *
     * @return string The unquoted table name.
     */
    public function unquoteSimpleTableName(string $name): string;
}
