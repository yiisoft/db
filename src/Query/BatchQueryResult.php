<?php

declare(strict_types=1);

namespace Yiisoft\Db\Query;

use Iterator;
use PDOException;
use Throwable;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Data\DataReader;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;

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
class BatchQueryResult implements Iterator
{
    private int $batchSize = 100;
    private ?ConnectionInterface $db = null;
    private bool $each = false;
    private $key;
    private ?Query $query = null;

    /**
     * @var DataReader|null the data reader associated with this batch query.
     */
    private ?DataReader $dataReader = null;

    /**
     * @var array|null the data retrieved in the current batch
     */
    private ?array $batch = null;

    /**
     * @var mixed the value for the current iteration
     */
    private $value;

    /**
     * @var int MSSQL error code for exception that is thrown when last batch is size less than specified batch size
     *
     * {@see https://github.com/yiisoft/yii2/issues/10023}
     */
    private int $mssqlNoMoreRowsErrorCode = -13;

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
        if ($this->dataReader !== null) {
            $this->dataReader->close();
        }

        $this->dataReader = null;
        $this->batch = null;
        $this->value = null;
        $this->key = null;
    }

    /**
     * Resets the iterator to the initial state.
     *
     * This method is required by the interface {@see Iterator}.
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
        if ($this->batch === null || !$this->each || ($this->each && next($this->batch) === false)) {
            $this->batch = $this->fetchData();
            reset($this->batch);
        }

        if ($this->each) {
            $this->value = current($this->batch);
            if ($this->query->getIndexBy() !== null) {
                $this->key = key($this->batch);
            } elseif (key($this->batch) !== null) {
                $this->key = $this->key === null ? 0 : $this->key + 1;
            } else {
                $this->key = null;
            }
        } else {
            $this->value = $this->batch;
            $this->key = $this->key === null ? 0 : $this->key + 1;
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
     * @return array
     */
    protected function getRows(): array
    {
        $rows = [];
        $count = 0;

        try {
            while ($count++ < $this->batchSize && ($row = $this->dataReader->read())) {
                $rows[] = $row;
            }
        } catch (PDOException $e) {
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
    #[\ReturnTypeWillChange]
    public function key()
    {
        return $this->key;
    }

    /**
     * Returns the current dataset.
     *
     * This method is required by the interface {@see \Iterator}.
     *
     * @return mixed the current dataset.
     */
    #[\ReturnTypeWillChange]
    public function current()
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
     * @return string|null
     */
    private function getDbDriverName(): ?string
    {
        return $this->db->getDriverName();
    }

    /**
     * {@see Query}
     *
     * @return Query
     */
    public function getQuery(): Query
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
     * @param Query $value the query object associated with this batch query. Do not modify this property directly
     * unless after {@see reset()} is called explicitly.
     *
     * @return $this
     */
    public function query(Query $value): self
    {
        $this->query = $value;

        return $this;
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

    /**
     * @param ConnectionInterface $value the DB connection to be used when performing batch query.
     *
     * @return $this
     */
    public function db(ConnectionInterface $value): self
    {
        $this->db = $value;

        return $this;
    }

    /**
     * @param bool $value whether to return a single row during each iteration.
     *
     * If false, a whole batch of rows will be returned in each iteration.
     *
     * @return $this
     */
    public function each(bool $value): self
    {
        $this->each = $value;

        return $this;
    }
}
