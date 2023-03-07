<?php

declare(strict_types=1);

namespace Yiisoft\Db\Query;

use Closure;
use Throwable;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Query\Data\DataReaderInterface;

use function current;
use function key;
use function next;
use function reset;

/**
 * Represents the result of a batch query execution.
 *
 * A batch query is a group of many SQL statements that are executed together as a single unit.
 */
class BatchQueryResult implements BatchQueryResultInterface
{
    protected int $batchSize = 100;
    private int|string|null $key = null;

    /**
     * @var DataReaderInterface|null The data reader associated with this batch query.
     */
    protected DataReaderInterface|null $dataReader = null;

    /**
     * @var array|null The data retrieved in the current batch.
     */
    private array|null $batch = null;

    private Closure|null $populateMethod = null;

    /**
     * @var mixed The value for the current iteration.
     */
    private mixed $value;

    public function __construct(private QueryInterface $query, private bool $each = false)
    {
    }

    public function __destruct()
    {
        $this->reset();
    }

    public function reset(): void
    {
        $this->dataReader = null;
        $this->batch = null;
        $this->value = null;
        $this->key = null;
    }

    public function rewind(): void
    {
        $this->reset();
        $this->next();
    }

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
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     *
     * @return array The data fetched.
     */
    protected function fetchData(): array
    {
        if ($this->dataReader === null) {
            $this->dataReader = $this->query->createCommand()->query();
        }

        $rows = $this->getRows();

        if ($this->populateMethod !== null) {
            return (array) ($this->populateMethod)($rows, $this->query->getIndexBy());
        }

        return $rows;
    }

    /**
     * Reads and collects rows for batch.
     */
    protected function getRows(): array
    {
        $rows = [];
        $count = 0;

        do {
            $this->dataReader?->next();
            /** @psalm-var array|bool $row */
            $row = $this->dataReader?->current();
        } while ($row && ($rows[] = $row) && ++$count < $this->batchSize);

        return $rows;
    }

    public function key(): int|string|null
    {
        return $this->key;
    }

    public function current(): mixed
    {
        return $this->value;
    }

    public function valid(): bool
    {
        return !empty($this->batch);
    }

    public function getQuery(): QueryInterface|null
    {
        return $this->query;
    }

    public function getBatchSize(): int
    {
        return $this->batchSize;
    }

    public function batchSize(int $value): self
    {
        $this->batchSize = $value;

        return $this;
    }

    public function setPopulatedMethod(Closure|null $populateMethod = null): static
    {
        $this->populateMethod = $populateMethod;

        return $this;
    }
}
