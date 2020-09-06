<?php

declare(strict_types=1);

namespace Yiisoft\Db\Data;

use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Data\Reader\CountableDataInterface;
use Yiisoft\Db\Query\Query;
use Yiisoft\Data\Reader\Sort;

class DataReaderProvider implements CountableDataInterface
{
    private ?ConnectionInterface $db = null;
    private ?string $sql = null;
    private array $params = [];
    private bool $pagination = false;
    private array $models = [];
    private array $keys = [];
    private ?Sort $sort = null;

    /**
     * @var string the column that is used as the key of the data models. This can be either a column name.
     *
     * If this is not set, the keys of the {@see models} array will be used.
     */
    private ?string $key = null;

    /**
     * Returns the data models in the current page.
     *
     * @return array the list of data models in the current page.
     */
    public function getModels(): array
    {
        $this->prepare();

        return $this->models;
    }

    /**
     * Returns the number of data models in the current page.
     *
     * @return int the number of data models in the current page.
     */
    public function count(): int
    {
        return \count($this->getModels());
    }

    /**
     * Returns the total number of data models.
     *
     * When {@see pagination} is false, this returns the same value as {@see count}, otherwise, it will call
     * {@see prepareTotalCount()} to get the count.
     *
     * @return int total number of possible data models.
     */
    public function getTotalCount()
    {
        if ($this->getPagination() === false) {
            return $this->count();
        } else {
            return $this->prepareTotalCount();
        }
    }

    public function getSort(): ?Sort
    {
        return $this->sort;
    }

    public function getPagination(): bool
    {
        return $this->pagination;
    }

    /**
     * Set the DB connection the application.
     *
     * @param ConnectionInterface $value
     *
     * @return self
     */
    public function db(ConnectionInterface $value): self
    {
        $this->db = $value;

        return $this;
    }

    /**
     * Set the SQL statement to be used for fetching data rows.
     *
     * @param string $sql
     *
     * @return self
     */
    public function sql(string $value): self
    {
        $this->sql = $value;

        return $this;
    }

    /**
     * Set parameters (name=>value) to be bound to the SQL statement.
     *
     * @param array $params
     *
     * @return self
     */
    public function params(array $value): self
    {
        $this->params = $value;

        return $this;
    }

    /**
     * Set the value of pagination.
     *
     * @param bool $value
     *
     * @return self
     */
    public function setPagination(bool $value): self
    {
        $this->pagination = $value;

        return $this;
    }

    /**
     * Prepares the data models and keys.
     *
     * This method will prepare the data models and keys that can be retrieved via {@see getModels()} and
     * {@see getKeys()}.
     *
     * This method will be implicitly called by {@see getModels()} and {@see getKeys()} if it has not been called
     * before.
     *
     * @param bool $forcePrepare whether to force data preparation even if it has been done before.
     */
    private function prepare()
    {
        $this->models = $this->prepareModels();
        $this->keys = $this->prepareKeys($this->models);
    }

    private function prepareModels()
    {
        if ($this->getPagination() === false && $this->getSort() === null) {
            return $this->db->createCommand($this->sql, $this->params)->queryAll();
        }

        $sql = $this->sql;
        $orders = [];
        $limit = $offset = null;

        $sql = $this->db->getQueryBuilder()->buildOrderByAndLimit($sql, $orders, $limit, $offset);

        return $this->db->createCommand($sql, $this->params)->queryAll();
    }

    private function prepareKeys(array $models)
    {
        $keys = [];

        if ($this->key !== null) {
            foreach ($models as $model) {
                if (\is_string($this->key)) {
                    $keys[] = $model[$this->key];
                } else {
                    $keys[] = \call_user_func($this->key, $model);
                }
            }

            return $keys;
        }

        return \array_keys($models);
    }

    private function prepareTotalCount(): int
    {
        return (new Query($this->db))->from(['sub' => "({$this->sql})"])->params($this->params)->count('*');
    }
}
