<?php

declare(strict_types=1);

namespace Yiisoft\Db\Query;

use Closure;

/**
 * Represents the result of a batch query execution.
 *
 * A batch query is a group of many SQL statements that are executed together as a single unit.
 *
 * @psalm-import-type IndexBy from QueryInterface
 * @psalm-import-type ResultCallback from QueryInterface
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
    private ?DataReaderInterface $dataReader = null;

    /**
     * @psalm-var IndexBy|null $indexBy
     */
    private Closure|string|null $indexBy = null;

    /**
     * @var Closure|null A callback function to process the result rows.
     * @psalm-var ResultCallback|null
     */
    private ?Closure $resultCallback = null;

    public function __construct(private readonly QueryInterface $query) {}

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

    public function key(): int
    {
        if ($this->index === -1) {
            $this->next();
        }

        return $this->index;
    }

    public function current(): array
    {
        if ($this->index === -1) {
            $this->next();
        }

        return $this->batch;
    }

    public function valid(): bool
    {
        if ($this->index === -1) {
            $this->next();
        }

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

    public function indexBy(Closure|string|null $indexBy): static
    {
        $this->indexBy = $indexBy;
        return $this;
    }

    public function resultCallback(?Closure $callback): static
    {
        $this->resultCallback = $callback;

        return $this;
    }

    /**
     * Fetches the next batch of data.
     *
     * @return array The data fetched.
     *
     * @psalm-return array<array<string,mixed>|object>
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
     * @psalm-return array<array<string,mixed>>
     */
    private function getRows(): array
    {
        $rows = [];

        $this->dataReader ??= $this->query->createCommand()->query()->indexBy($this->indexBy);

        $isContinuousIndex = $this->indexBy === null;
        $startIndex = $isContinuousIndex ? ($this->index + 2) * $this->batchSize : 0;

        for (
            $leftCount = $this->batchSize;
            $leftCount > 0 && $this->dataReader->valid();
            --$leftCount, $this->dataReader->next()
        ) {
            /** @var int|string $key */
            $key = $isContinuousIndex ? $startIndex - $leftCount : $this->dataReader->key();
            /** @psalm-var array<string, mixed> */
            $rows[$key] = $this->dataReader->current();
        }

        return $rows;
    }
}
