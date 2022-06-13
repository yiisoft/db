<?php

declare(strict_types=1);

namespace Yiisoft\Db\Query;

use Iterator;
use PDOException;
use Throwable;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidCallException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Query\Data\DataReaderInterface;

use function current;
use function key;
use function next;
use function reset;

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
final class BatchQueryResult implements Iterator
{
    private int $batchSize = 100;
    private int|string|null $key = null;

    /**
     * @var DataReaderInterface|null the data reader associated with this batch query.
     */
    private ?DataReaderInterface $dataReader = null;

    /**
     * @var array|null the data retrieved in the current batch
     */
    private ?array $batch = null;

    /**
     * @var mixed the value for the current iteration
     */
    private mixed $value;

    /**
     * @var int MSSQL error code for exception that is thrown when last batch is size less than specified batch size
     *
     * {@see https://github.com/yiisoft/yii2/issues/10023}
     */
    private int $mssqlNoMoreRowsErrorCode = -13;

    public function __construct(
        private ConnectionInterface $db,
        private QueryInterface $query,
        private bool $each = false
    ) {
    }

    /**
     * @throws InvalidCallException
     */
    public function __destruct()
    {
        $this->reset();
    }

    /**
     * Resets the batch query.
     *
     * This method will clean up the existing batch query so that a new batch query can be performed.
     */
    public function reset(): void
    {
        $this->dataReader = null;
        $this->batch = null;
        $this->value = null;
        $this->key = null;
    }

    /**
     * Resets the iterator to the initial state.
     *
     * This method is required by the interface {@see Iterator}.
     *
     * @throws InvalidCallException
     */
    public function rewind(): void
    {
        $this->reset();
        $this->next();
    }

    /**
     * Moves the internal pointer to the next dataset.
     *
     * This method is required by the interface {@see Iterator}.
     */
    public function next(): void
    {
        if ($this->batch === null || !$this->each || (next($this->batch) === false)) {
            $this->batch = $this->fetchData();
            reset($this->batch);
        }

        if ($this->each) {
            $this->value = current($this->batch);

            if ($this->query->getIndexBy() !== null) {
                $this->key = key($this->batch);
            } elseif (key($this->batch) !== null) {
                $this->key = $this->key === null ? 0 : (int) $this->key + 1;
            } else {
                $this->key = null;
            }
        } else {
            $this->value = $this->batch;
            $this->key = $this->key === null ? 0 : (int) $this->key + 1;
        }
    }

    /**
     * Fetches the next batch of data.
     *
     * @throws Exception|InvalidConfigException|Throwable
     *
     * @return array the data fetched.
     */
    protected function fetchData(): array
    {
        if ($this->dataReader === null) {
            $this->dataReader = $this->query->createCommand()->query();
        }

        $rows = $this->getRows();

        return $this->query->populate($rows);
    }

    /**
     * Reads and collects rows for batch.
     *
     * @throws InvalidCallException
     *
     * @return array
     *
     * @psalm-suppress MixedArrayAccess
     */
    protected function getRows(): array
    {
        $rows = [];
        $count = 0;

        try {
            do {
                $this->dataReader?->next();
                /** @psalm-var array|bool $row */
                $row = $this->dataReader?->current();
            } while ($row && ($rows[] = $row) && ++$count < $this->batchSize);
        } catch (PDOException $e) {
            /** @var int|null */
            $errorCode = $e->errorInfo[1] ?? null;

            if ($this->getDbDriverName() !== 'sqlsrv' || $errorCode !== $this->mssqlNoMoreRowsErrorCode) {
                throw $e;
            }
        }

        return $rows;
    }

    /**
     * Returns the index of the current dataset.
     *
     * This method is required by the interface {@see Iterator}.
     *
     * @return int|string|null the index of the current row.
     */
    public function key(): int|string|null
    {
        return $this->key;
    }

    /**
     * Returns the current dataset.
     *
     * This method is required by the interface {@see Iterator}.
     *
     * @return mixed the current dataset.
     */
    public function current(): mixed
    {
        return $this->value;
    }

    /**
     * Returns whether there is a valid dataset at the current position.
     *
     * This method is required by the interface {@see Iterator}.
     *
     * @return bool whether there is a valid dataset at the current position.
     */
    public function valid(): bool
    {
        return !empty($this->batch);
    }

    /**
     * Gets db driver name from the db connection that is passed to the `batch()` or `each()`.
     *
     * @return string
     */
    private function getDbDriverName(): string
    {
        return $this->db->getDriverName();
    }

    public function getQuery(): QueryInterface|null
    {
        return $this->query;
    }

    /**
     * {@see batchSize}
     *
     * @return int
     */
    public function getBatchSize(): int
    {
        return $this->batchSize;
    }

    /**
     * @param int $value the number of rows to be returned in each batch.
     *
     * @return $this
     */
    public function batchSize(int $value): self
    {
        $this->batchSize = $value;

        return $this;
    }
}
