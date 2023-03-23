<?php

declare(strict_types=1);

namespace Yiisoft\Db\Query;

use Throwable;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;

/**
 * A query function is a function that's called in a query to perform operation on the data selected or
 * updated.
 *
 * Examples of query functions might include {@see count()}, {@see sum()}, {@see average()}, and {@see max()}.
 */
interface QueryFunctionsInterface
{
    /**
     * Returns the average of the specified column values.
     *
     * @param string $q The column name or expression.
     *
     * @throws Throwable
     *
     * @return float|int|string|null The average of the specified column values.
     *
     * Note: Make sure you quote column names in the expression.
     */
    public function average(string $q): int|float|null|string;

    /**
     * Returns the number of records.
     *
     * @param string $q The `COUNT` expression. Defaults to '*'.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     *
     * @return int|string Number of records. The result may be a string depending on the underlying database engine and
     * to support integer values higher than a 32bit PHP integer can handle.
     *
     * Note: Make sure you quote column names in the expression.
     */
    public function count(string $q = '*'): int|string;

    /**
     * Returns the maximum of the specified column values.
     *
     * @param string $q The column name or expression.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     *
     * @return float|int|string|null The maximum of the specified column values.
     *
     * Note: Make sure you quote column names in the expression.
     */
    public function max(string $q): int|float|null|string;

    /**
     * Returns the minimum of the specified column values.
     *
     * @param string $q The column name or expression.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     *
     * @return float|int|string|null The minimum of the specified column values.
     *
     * Note: Make sure you quote column names in the expression.
     */
    public function min(string $q): int|float|null|string;

    /**
     * Returns the sum of the specified column values.
     *
     * @param string $q The column name or expression.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     *
     * @return float|int|string|null The sum of the specified column values.
     *
     * Note: Make sure you quote column names in the expression.
     */
    public function sum(string $q): int|float|null|string;
}
