<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder;

use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Expression\ExpressionBuilderInterface;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Schema\Builder\ColumnInterface;
use Yiisoft\Db\Schema\QuoterInterface;

/**
 * Defines the common interface to be implemented by query builder classes.
 *
 * A query builder is mainly responsible for creating SQL statements based on specifications given in a
 * {@see QueryInterface} object.
 *
 * A query builder should also support creating SQL statements for DBMS-specific features such as creating indexes,
 * adding foreign keys, etc. through the methods defined in {@see DDLQueryBuilderInterface}, {@see DMLQueryBuilderInterface}
 * and {@see DQLQueryBuilderInterface}.
 *
 * @psalm-import-type ParamsType from ConnectionInterface
 */
interface QueryBuilderInterface extends DDLQueryBuilderInterface, DMLQueryBuilderInterface, DQLQueryBuilderInterface
{
    /**
     * Helper method to add `$value` to `$params` array using {@see PARAM_PREFIX}.
     *
     * @param array $params Passed by reference.
     *
     * @return string The placeholder name in $params array.
     *
     * @psalm-param ParamsType $params
     */
    public function bindParam(mixed $value, array &$params = []): string;

    /**
     * Converts an abstract column type into a physical column type.
     *
     * The conversion is done using the type map specified in {@see typeMap}.
     * The following abstract column types are supported (using MySQL as an example to explain the corresponding
     * physical types):
     *
     * - `pk`: an auto-incremental primary key type, will be converted into "int(11) NOT NULL AUTO_INCREMENT PRIMARY
     *    KEY"
     * - `bigpk`: an auto incremental primary key type, will be converted into "bigint(20) NOT NULL AUTO_INCREMENT
     *    PRIMARY KEY"
     * - `upk`: an unsigned auto incremental primary key type, will be converted into "int(10) UNSIGNED NOT NULL
     *    AUTO_INCREMENT PRIMARY KEY"
     * - `char`: char type, will be converted into "char(1)"
     * - `string`: string type, will be converted into "varchar(255)"
     * - `text`: a long string type, will be converted into "text"
     * - `smallint`: a small integer type, will be converted into "smallint(6)"
     * - `integer`: integer type, will be converted into "int(11)"
     * - `bigint`: a big integer type, will be converted into "bigint(20)"
     * - `boolean`: boolean type, will be converted into "tinyint(1)"
     * - `float``: float number type, will be converted into "float"
     * - `decimal`: decimal number type, will be converted into "decimal"
     * - `datetime`: datetime type, will be converted into "datetime"
     * - `timestamp`: timestamp type, will be converted into "timestamp"
     * - `time`: time type, will be converted into "time"
     * - `date`: date type, will be converted into "date"
     * - `money`: money type, will be converted into "decimal(19,4)"
     * - `binary`: binary data type, will be converted into "blob"
     *
     * If the abstract type has two or more parts separated by spaces (such as "string NOT NULL"), then only the first
     * part will be converted, and the rest of the parts will be appended to the converted result.
     *
     * For example, 'string NOT NULL' is converted to 'varchar(255) NOT NULL'.
     *
     * For some abstract types, you can also specify a length or precision constraint by appending it in round brackets
     * directly to the type.
     *
     * For example, `string(32)` will be converted into "varchar(32)" on a MySQL database.
     * If the underlying DBMS doesn't support these kinds of constraints for a type, it will be ignored.
     *
     * If a type can't be found in {@see typeMap}, it will be returned without any change.
     *
     * @param ColumnInterface|string $type Abstract column type.
     *
     * @return string Physical column type.
     */
    public function getColumnType(ColumnInterface|string $type): string;

    /**
     * Gets an object of {@see ExpressionBuilderInterface} that's suitable for $expression.
     *
     * Uses {@see AbstractDQLQueryBuilder::expressionBuilders} an array to find a suitable builder class.
     *
     * @param ExpressionInterface $expression The expression to build.
     *
     * @throws InvalidArgumentException When expression building isn't supported by this QueryBuilder.
     */
    public function getExpressionBuilder(ExpressionInterface $expression): object;

    /**
     * @return QuoterInterface The quoter instance.
     */
    public function quoter(): QuoterInterface;
}
