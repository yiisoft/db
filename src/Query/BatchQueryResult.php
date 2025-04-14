<?php

declare(strict_types=1);

namespace Yiisoft\Db\Query;

use Closure;
use Throwable;

/**
 * Represents the result of a batch query execution.
 *
 * A batch query is a group of many SQL statements that are executed together as a single unit.
 *
 * @psalm-import-type ResultCallback from BatchQueryResultInterface
 */
final class BatchQueryResult implements BatchQueryResultInterface
{
    private int $batchSize = 100;
    private int $index = -1;
    /**
     * @var array The data retrieved in the current batch.
     */
    private array $batch = [];
    /**
     * @var DataReaderInterface|null The data reader associated with this batch query.
     */
    private DataReaderInterface|null $dataReader = null;
    /**
     * @var Closure|null A callback function to process the result rows.
     * @psalm-var ResultCallback|null
     */
    private Closure|null $resultCallback = null;

    public function __construct(private readonly QueryInterface $query)
    {
    }

    public function rewind(): void
    {
        if ($this->index === 0) {
            return;
        }

        $this->dataReader = null;
        $this->batch = $this->fetchData();
        $this->index = 0;
    }

    public function next(): void
    {
        $this->batch = $this->fetchData();
        ++$this->index;
    }

    /**
     * Fetches the next batch of data.
     *
     * @throws Throwable
     *
     * @return array The data fetched.
     */
    private function fetchData(): array
    {
        $rows = $this->getRows();

        if ($this->resultCallback === null || empty($rows)) {
            return $rows;
        }

        return ($this->resultCallback)($rows);
    }

    /**
     * Reads and collects rows for batch.
     *
     * @psalm-return list<array>
     */
    private function getRows(): array
    {
        $rows = [];

        $this->dataReader ??= $this->query->createCommand()->query();

        for (
            $leftCount = $this->batchSize;
            $leftCount > 0 && $this->dataReader->valid();
            --$leftCount, $this->dataReader->next()
        ) {
            /** @var array */
            $rows[] = $this->dataReader->current();
        }

        return $rows;
    }

    public function key(): int
    {
        return $this->index;
    }

    public function current(): array
    {
        return $this->batch;
    }

    public function valid(): bool
    {
        return !empty($this->batch);
    }

    public function getQuery(): QueryInterface
    {
        return $this->query;
    }

    public function getBatchSize(): int
    {
        return $this->batchSize;
    }

    public function batchSize(int $value): static
    {
        $this->batchSize = $value;

        return $this;
    }

    public function resultCallback(Closure|null $callback): static
    {
        $this->resultCallback = $callback;

        return $this;
    }
}
