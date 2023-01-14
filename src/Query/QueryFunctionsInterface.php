<?php

declare(strict_types=1);

namespace Yiisoft\Db\Query;

use Throwable;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;

/**
 * The QueryFunctionsInterface defines an interface for query functions. A query function is a function that can be
 * called in a query to perform some operation on the data being selected or updated. Examples of query functions might
 * include `COUNT()`, `SUM()`, `AVG()`, and `MAX()`. The interface defines methods for building and modifying queries,
 * such as adding or removing query functions.
 */
interface QueryFunctionsInterface
{
    /**
     * Returns the average of the specified column values.
     *
     * @param string $q the column name or expression.
     * Make sure you properly [quote](guide:db-dao#quoting-table-and-column-names) column names in the expression.
     *
     * @throws Throwable
     *
     * @return float|int|string|null the average of the specified column values.
     */
    public function average(string $q): int|float|null|string;

    /**
     * Returns the number of records.
     *
     * @param string $q the COUNT expression. Defaults to '*'.
     * Make sure you properly [quote](guide:db-dao#quoting-table-and-column-names) column names in the expression.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     *
     * @return int|string number of records. The result may be a string depending on the underlying database
     * engine and to support integer values higher than a 32bit PHP integer can handle.
     */
    public function count(string $q = '*'): int|string;

    /**
     * Returns the maximum of the specified column values.
     *
     * @param string $q the column name or expression.
     * Make sure you properly [quote](guide:db-dao#quoting-table-and-column-names) column names in the expression.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     *
     * @return float|int|string|null the maximum of the specified column values.
     */
    public function max(string $q): int|float|null|string;

    /**
     * Returns the minimum of the specified column values.
     *
     * @param string $q the column name or expression.
     * Make sure you properly [quote](guide:db-dao#quoting-table-and-column-names) column names in the expression.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     *
     * @return float|int|string|null the minimum of the specified column values.
     */
    public function min(string $q): int|float|null|string;

    /**
     * Returns the sum of the specified column values.
     *
     * @param string $q the column name or expression.
     * Make sure you properly [quote](guide:db-dao#quoting-table-and-column-names) column names in the expression.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     *
     * @return float|int|string|null the sum of the specified column values.
     */
    public function sum(string $q): int|float|null|string;
}
