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
 * Instead, you obtain it by calling {@see Query::batch()} or {@see Query::each()}.
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
 *
 * foreach ($query->each() as $user) {
 *     // $user represents the next row in the query result
 * }
 * ```
 *
 * @extends Iterator<int|string, mixed>
 *
 * @psalm-type PopulateClosure=Closure(array[],Closure|string|null): mixed
 */
interface BatchQueryResultInterface extends Iterator
{
    /**
     * Resets the batch query.
     *
     * This method will clean up the existing batch query so that a new batch query can be performed.
     */
    public function reset(): void;

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
     * @return int|string|null The index of the current row.
     */
    public function key(): int|string|null;

    /**
     * Returns the current dataset.
     *
     * This method is required by the interface {@see Iterator}.
     *
     * @return mixed The current dataset.
     */
    public function current(): mixed;

    /**
     * Returns whether there is a valid dataset at the current position.
     *
     * This method is required by the interface {@see Iterator}.
     *
     * @return bool Whether there is a valid dataset at the current position.
     */
    public function valid(): bool;

    public function getQuery(): QueryInterface|null;

    /**
     * @see batchSize()
     */
    public function getBatchSize(): int;

    /**
     * @param int $value The number of rows to return in each batch.
     */
    public function batchSize(int $value): self;

    /**
     * @psalm-param PopulateClosure|null $populateMethod
     */
    public function setPopulatedMethod(Closure|null $populateMethod = null): self;
}
