<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder;

use Yiisoft\Db\Command\ParamInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Connection\ServerInfoInterface;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Expression\ExpressionBuilderInterface;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Schema\Column\ColumnFactoryInterface;
use Yiisoft\Db\Schema\Column\ColumnInterface;
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
     * Builds column definition based on given column instance.
     *
     * @param ColumnInterface|string $column the column instance or string column definition which should be
     * converted into a database string representation.
     *
     * @return string the SQL column definition.
     */
    public function buildColumnDefinition(ColumnInterface|string $column): string;

    /**
     * Returns the column definition builder for the current DBMS.
     */
    public function getColumnDefinitionBuilder(): ColumnDefinitionBuilderInterface;

    /**
     * Returns the column factory for creating column instances.
     */
    public function getColumnFactory(): ColumnFactoryInterface;

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
     * Returns {@see ServerInfoInterface} instance that provides information about the database server.
     */
    public function getServerInfo(): ServerInfoInterface;

    /**
     * @return QuoterInterface The quoter instance.
     */
    public function getQuoter(): QuoterInterface;

    /**
     * Converts a {@see ParamInterface} object to its SQL representation and quotes it if necessary.
     * Used when the bind parameter cannot be used in the SQL query.
     */
    public function prepareParam(ParamInterface $param): string;

    /**
     * Converts a value to its SQL representation and quotes it if necessary.
     * Used when the bind parameter cannot be used in the SQL query.
     */
    public function prepareValue(mixed $value): string;

    /**
     * Returns maximum number of parameters for a DBMS.
     * @return int
     */
    public function getParametersLimit(): int;
}
