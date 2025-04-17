<?php

declare(strict_types=1);

namespace Yiisoft\Db\Query;

use Closure;
use Iterator;
use Throwable;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;

/**
 * This interface represents a batch query from which you can retrieve data in batches.
 *
 * You usually don't instantiate BatchQueryResult directly.
 *
 * Instead, you obtain it by calling {@see Query::batch()}.
 *
 * Because BatchQueryResult implements the {@see Iterator} interface, you can iterate it to obtain a batch of data in
 * each iteration.
 *
 * For example,
 *
 * ```php
 * $query = (new Query)->from('user');
 *
 * foreach ($query->batch() as $i => $users) {
 *     // $users represents the rows in the $i-th batch
 * }
 * ```
 *
 * @extends Iterator<int, array>
 *
 * @psalm-import-type IndexBy from QueryInterface
 * @psalm-type ResultCallback = Closure(non-empty-list<array>):non-empty-array<array|object>
 */
interface BatchQueryResultInterface extends Iterator
{
    /**
     * Resets the iterator to the initial state.
     *
     * This method is required by the interface {@see Iterator}.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function rewind(): void;

    /**
     * Moves the internal pointer to the next dataset.
     *
     * This method is required by the interface {@see Iterator}.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function next(): void;

    /**
     * Returns the index of the current dataset.
     *
     * This method is required by the interface {@see Iterator}.
     *
     * @return int The index of the current row.
     */
    public function key(): int;

    /**
     * Returns the current dataset.
     *
     * This method is required by the interface {@see Iterator}.
     *
     * @return array The current dataset.
     */
    public function current(): array;

    /**
     * Returns whether there is a valid dataset at the current position.
     *
     * This method is required by the interface {@see Iterator}.
     *
     * @return bool Whether there is a valid dataset at the current position.
     */
    public function valid(): bool;

    public function getQuery(): QueryInterface;

    /**
     * @see batchSize()
     */
    public function getBatchSize(): int;

    /**
     * @param int $value The number of rows to return in each batch.
     */
    public function batchSize(int $value): static;

    /**
     * Sets a callback function to be called for the result of the query.
     *
     * @psalm-param ResultCallback|null $callback
     */
    public function resultCallback(Closure|null $callback): static;
}
