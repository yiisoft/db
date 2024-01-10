<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema;

use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Expression\ExpressionInterface;

/**
 * This interface provides a set of methods to quote table and column names, values, and other SQL
 * expressions independently of the database.
 */
interface QuoterInterface
{
    /**
     * Clean-up table names and aliases.
     *
     * Both aliases and names are enclosed into `{{ and }}`.
     *
     * @param array $tableNames Non-empty array.
     *
     * @throws InvalidArgumentException
     *
     * @psalm-return array<array-key, ExpressionInterface|string>
     */
    public function cleanUpTableNames(array $tableNames): array;

    /**
     * Splits full table name into parts.
     *
     * @param string $name The full name of the table.
     * @param bool $withColumn Deprecated. Will be removed in version 2.0.0.
     *
     * @return string[] The table name parts.
     */
    public function getTableNameParts(string $name, bool $withColumn = false): array;

    /**
     * Ensures name is wrapped with `{{ and }}`.
     *
     * @param string $name The name to quote.
     *
     * @return string The quoted name.
     */
    public function ensureNameQuoted(string $name): string;

    /**
     * Ensures name of the column is wrapped with `[[ and ]]`.
     *
     * @param string $name The name to quote.
     *
     * @return string The quoted name.
     */
    public function ensureColumnName(string $name): string;

    /**
     * Quotes a column name for use in a query.
     *
     * If the column name has a prefix, it quotes the prefix.
     * If the column name is already quoted or has '(', '[[' or '{{', then this method does nothing.
     *
     * @param string $name The column name to quote.
     *
     * @return string The quoted column name.
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
     * @param string $name The column name to quote.
     *
     * @return string The quoted column name.
     */
    public function quoteSimpleColumnName(string $name): string;

    /**
     * Quotes a simple table name for use in a query.
     *
     * A simple table name should contain the table name only without any schema prefix. If the table name is already
     * quoted, this method will do nothing.
     *
     * @param string $name The table name to quote.
     *
     * @return string The quoted table name.
     */
    public function quoteSimpleTableName(string $name): string;

    /**
     * Processes an SQL statement by quoting table and column names that are inside within double brackets.
     *
     * Tokens inside within double curly brackets are treated as table names, while tokens inside within double square
     * brackets are column names. They will be quoted as such.
     *
     * Also, the percentage character "%" at the beginning or ending of a table name will be replaced with
     * {@see \Yiisoft\Db\Connection\ConnectionInterface::setTablePrefix()}.
     *
     * @param string $sql The SQL statement to quote.
     *
     * @return string The quoted SQL statement.
     */
    public function quoteSql(string $sql): string;

    /**
     * Quotes a table name for use in a query.
     *
     * If the table name has a schema prefix, then it will also quote the prefix.
     *
     * If the table name is already quoted or has `(` or `{{`, then this method will do nothing.
     *
     * @param string $name The table name to quote.
     *
     * @return string The quoted table name.
     *
     * @see quoteSimpleTableName()
     */
    public function quoteTableName(string $name): string;

    /**
     * Quotes a string value for use in a query.
     *
     * Note: That if the parameter isn't a string, it will be returned without change.
     * Attention: The usage of this method isn't safe.
     * Use prepared statements.
     *
     * @param mixed $value The value to quote.
     *
     * @return mixed The quoted value.
     */
    public function quoteValue(mixed $value): mixed;

    /**
     * Unquotes a simple column name.
     *
     * A simple column name should contain the column name only without any prefix.
     *
     * If the column name isn't quoted or is the asterisk character '*', this method will do nothing.
     *
     * @param string $name The column name to unquote.
     *
     * @return string The unquoted column name.
     */
    public function unquoteSimpleColumnName(string $name): string;

    /**
     * Unquotes a simple table name.
     *
     * A simple table name should contain the table name only without any schema prefix.
     *
     * If the table name isn't quoted, this method will do nothing.
     *
     * @param string $name The table name to unquote.
     *
     * @return string The unquoted table name.
     */
    public function unquoteSimpleTableName(string $name): string;
}
