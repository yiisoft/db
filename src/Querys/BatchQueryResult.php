<?php

declare(strict_types=1);

namespace Yiisoft\Db\Querys;

use Yiisoft\Db\Data\DataReader;
use Yiisoft\Db\Drivers\Connection;
use Yiisoft\Db\Exceptions\Exception;

/**
 * BatchQueryResult represents a batch query from which you can retrieve data in batches.
 *
 * You usually do not instantiate BatchQueryResult directly. Instead, you obtain it by calling {@see Query::batch()} or
 * {@see Query::each()}. Because BatchQueryResult implements the {@see \Iterator} interface, you can iterate it to
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
class BatchQueryResult implements \Iterator
{
    private int $batchSize = 100;
    private ?Connection $db = null;
    private bool $each = false;
    private $key;
    private ?Query $query = null;

    /**
     * @var DataReader the data reader associated with this batch query.
     */
    private ?DataReader $dataReader = null;

    /**
     * @var array the data retrieved in the current batch
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

    /**
     * Destructor.
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
     * This method is required by the interface {@see \Iterator}.
     */
    public function rewind(): void
    {
        $this->reset();
        $this->next();
    }

    /**
     * Moves the internal pointer to the next dataset.
     *
     * This method is required by the interface {@see \Iterator}.
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
     * @return array the data fetched
     *
     * @throws Exception
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
     * Reads and collects rows for batch
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
        } catch (\PDOException $e) {
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
     * This method is required by the interface {@see \Iterator}.
     *
     * @return string|int|null the index of the current row.
     */
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
     * Gets db driver name from the db connection that is passed to the `batch()`, if it is not passed it uses
     * connection from the active record model
     *
     * @return string|null
     */
    private function getDbDriverName(): ?string
    {
        if (empty($this->db->getDriverName())) {
            return $this->db->getDriverName();
        }

        if (!empty($this->batch)) {
            $key = array_keys($this->batch)[0];
            if (empty($this->batch[$key]->db->getDriverName())) {
                return $this->batch[$key]->db->getDriverName();
            }
        }

        return null;
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
     */
    public function setQuery(Query $value): void
    {
        $this->query = $value;
    }

    /**
     * @param int $value the number of rows to be returned in each batch.
     */
    public function setBatchSize(int $value): void
    {
        $this->batchSize = $value;
    }

    /**
     * @param Connection $value the DB connection to be used when performing batch query.
     */
    public function setDb(Connection $value): void
    {
        $this->db = $value;
    }

    /**
     * @param bool $value whether to return a single row during each iteration.
     *
     * If false, a whole batch of rows will be returned in each iteration.
     */
    public function setEach(bool $value): void
    {
        $this->each = $value;
    }
}
