<?php
declare(strict_types=1);

namespace Yiisoft\Db;

/**
 * BatchQueryResult represents a batch query from which you can retrieve data in batches.
 *
 * You usually do not instantiate BatchQueryResult directly. Instead, you obtain it by
 * calling {@see Query::batch()} or {@see Query::each()}. Because BatchQueryResult implements the {@see \Iterator}
 * interface, you can iterate it to obtain a batch of data in each iteration. For example,
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
class BatchQueryResult extends BaseObject implements \Iterator
{
    /**
     * @var Connection the DB connection to be used when performing batch query.
     *                 If null, the "db" application component will be used.
     */
    private $db;

    /**
     * @var Query the query object associated with this batch query.
     *            Do not modify this property directly unless after [[reset()]] is called explicitly.
     */
    public $query;
    /**
     * @var int the number of rows to be returned in each batch.
     */
    public $batchSize = 100;
    /**
     * @var bool whether to return a single row during each iteration.
     *           If false, a whole batch of rows will be returned in each iteration.
     */
    public $each = false;

    /**
     * @var DataReader the data reader associated with this batch query.
     */
    private $dataReader;

    /**
     * @var array the data retrieved in the current batch
     */
    private $batch;

    /**
     * @var mixed the value for the current iteration
     */
    private $value;

    /**
     * @var string|int the key for the current iteration
     */
    private $key;

    /**
     * Destructor.
     */
    public function __destruct()
    {
        // make sure cursor is closed
        $this->reset();
    }

    /**
     * Resets the batch query.
     *
     * This method will clean up the existing batch query so that a new batch query can be performed.
     */
    public function reset()
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
    public function rewind()
    {
        $this->reset();
        $this->next();
    }

    /**
     * Moves the internal pointer to the next dataset.
     *
     * This method is required by the interface {@see \Iterator}.
     */
    public function next()
    {
        if ($this->batch === null || !$this->each || $this->each && next($this->batch) === false) {
            $this->batch = $this->fetchData();
            reset($this->batch);
        }

        if ($this->each) {
            $this->value = current($this->batch);
            if ($this->query->indexBy !== null) {
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
    protected function fetchData()
    {
        if ($this->dataReader === null) {
            $this->dataReader = $this->query->createCommand($this->db)->query();
        }

        $rows = $this->getRows();

        return $this->query->populate($rows);
    }

    /**
     * Reads and collects rows for batch
     *
     * @return array
     */
    protected function getRows()
    {
        $rows = [];
        $count = 0;

        try {
            while ($count++ < $this->batchSize && ($row = $this->dataReader->read())) {
                $rows[] = $row;
            }
        } catch (\PDOException $e) {
            $errorCode = isset($e->errorInfo[1]) ? $e->errorInfo[1] : null;
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
     * @return int the index of the current row.
     */
    public function key(): int
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
    private function getDbDriverName()
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
}
