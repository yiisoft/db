<?php

declare(strict_types=1);

namespace Yiisoft\Db\Query;

use Iterator;
use Yiisoft\Db\Exception\InvalidCallException;

/**
 * BatchQueryResult represents a batch query from which you can retrieve data in batches.
 *
 * You usually do not instantiate BatchQueryResult directly. Instead, you obtain it by calling {@see Query::batch()} or
 * {@see Query::each()}. Because BatchQueryResult implements the {@see Iterator} interface, you can iterate it to
 * obtain a batch of data in each iteration.
 *
 * For example,
 *
 * ```php
 * $query = (new Query)->from('user');
 * foreach ($query->batch() as $i => $users) {
 *     // $users represents the rows in the $i-th batch
 * }
 * foreach ($query->each() as $user) {
 * }
 * ```
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
     * @throws InvalidCallException
     */
    public function rewind(): void;

    /**
     * Moves the internal pointer to the next dataset.
     *
     * This method is required by the interface {@see Iterator}.
     */
    public function next(): void;

    /**
     * Returns the index of the current dataset.
     *
     * This method is required by the interface {@see Iterator}.
     *
     * @return int|string|null the index of the current row.
     */
    public function key(): int|string|null;

    /**
     * Returns the current dataset.
     *
     * This method is required by the interface {@see Iterator}.
     *
     * @return mixed the current dataset.
     */
    public function current(): mixed;

    /**
     * Returns whether there is a valid dataset at the current position.
     *
     * This method is required by the interface {@see Iterator}.
     *
     * @return bool whether there is a valid dataset at the current position.
     */
    public function valid(): bool;

    public function getQuery(): QueryInterface|null;

    /**
     * {@see batchSize}
     *
     * @return int
     */
    public function getBatchSize(): int;

    /**
     * @param int $value the number of rows to be returned in each batch.
     *
     * @return $this
     */
    public function batchSize(int $value): self;
}
