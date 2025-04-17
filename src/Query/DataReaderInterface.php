<?php

declare(strict_types=1);

namespace Yiisoft\Db\Query;

use Closure;
use Countable;
use Iterator;

/**
 * This interface represents a forward-only stream of rows from a query result set.
 *
 * To read the current row of data just iterate on it.
 *
 * For example,
 *
 * ```php
 * $command = $connection->createCommand('SELECT * FROM post');
 * $reader = $command->query();
 *
 * foreach ($reader as $row) {
 *     $rows[] = $row;
 * }
 *
 * Note: That since DataReader is a forward-only stream, you can only traverse it once. Doing it the second time will
 * throw an exception.
 *
 * @extends Iterator<int|string|null, array|false>
 *
 * @psalm-import-type IndexBy from QueryInterface
 * @psalm-import-type ResultCallbackOne from QueryInterface
 */
interface DataReaderInterface extends Iterator, Countable
{
    /**
     * Returns the index of the current row or null if {@see indexBy} property is specified and there is no row
     * at the current position.
     *
     * This method is required by the interface {@see Iterator}.
     */
    public function key(): int|string|null;

    /**
     * Returns the current row or false if there is no row at the current position.
     *
     * This method is required by the interface {@see Iterator}.
     */
    public function current(): array|object|false;

    /**
     * Sets `indexBy` property.
     *
     * @param Closure|string|null $indexBy The name of the column by which the query results should be indexed by.
     * This can also be a `Closure` instance (for example, anonymous function) that returns the index value based
     * on the given row data.
     *
     * The signature of the callable should be:
     *
     * ```php
     * function (array $row): array-key
     * {
     *     // return the index value corresponding to $row
     * }
     * ```
     *
     * @psalm-param IndexBy|null $indexBy
     */
    public function indexBy(Closure|string|null $indexBy): static;

    /**
     * Sets the callback, to be called on all rows of the query result before returning them.
     *
     * For example:
     *
     * ```php
     * function (array $rows): array {
     *     foreach ($rows as &$row) {
     *         $row['name'] = strtoupper($row['name']);
     *     }
     *     return $rows;
     * }
     * ```
     *
     * @psalm-param ResultCallbackOne|null $resultCallback
     */
    public function resultCallback(Closure|null $resultCallback): static;
}
