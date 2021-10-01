<?php

declare(strict_types=1);

namespace Yiisoft\Db\DataReader;

use Generator;
use InvalidArgumentException;
use RuntimeException;
use Yiisoft\Data\Reader\DataReaderInterface;
use Yiisoft\Data\Reader\Filter\FilterInterface;
use Yiisoft\Data\Reader\Filter\FilterProcessorInterface;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Db\Processor\All;
use Yiisoft\Db\Processor\Equals;
use Yiisoft\Db\Processor\QueryProcessorInterface;
use Yiisoft\Db\Query\Query;


class QueryDataReader implements DataReaderInterface
{
    private Query $_query;

    private ?Sort $sort = null;
    private ?FilterInterface $filter = null;

    private int $limit = 0;
    private int $offset = 0;

    private ?int $_count = null;
    private ?array $_data = null;

    private array $filterProcessors = [];


    public function __construct(Query $query)
    {
        $this->_query = $query;

        $this->filterProcessors = $this->withFilterProcessors(
            new All(),
            new Equals()
        )->filterProcessors;
    }

    public function __clone()
    {
        $this->_data = null;
    }

    public function getIterator(): Generator
    {
        $query = $this->prepareQuery();

        foreach ($query->each() as $row) {
            yield $row;
        }
    }

    public function count(): int
    {
        if ($this->_count === null)
        {
            $query = $this->prepareQuery();
            $query->offset(null);
            $query->limit(null);
            $query->orderBy('');

            $this->_count = $query->count();
        }

        return $this->_count;
    }

    private function prepareQuery(): Query
    {
        $query = $this->applyFilter(clone $this->_query);

        if ($this->limit) {
            $query->limit($this->limit);
        }

        if ($this->offset) {
            $query->offset($this->offset);
        }

        if ($this->sort && $order = $this->sort->getOrder())
        {
            foreach ($order as $name => $direction) {
                $query->addOrderBy([$name => $direction === 'desc' ? SORT_DESC : SORT_ASC]);
            }
        }

        return $query;
    }

    protected function applyFilter(Query $query): Query
    {
        if ($this->filter === null) {
            return $query;
        }

        $operation = $this->filter::getOperator();
        $processor = $this->filterProcessors[$operation] ?? null;

        if (!isset($this->filterProcessors[$operation])) {
            throw new RuntimeException('Operation "%s" is not supported', $operation);
        }

        return $this->filterProcessors[$operation]->apply($query, $this->filter);
    }


    public function withOffset(int $offset): self
    {
        $new = clone $this;
        $new->offset = $offset;

        return $new;
    }

    public function withLimit(int $limit): self
    {
        if ($limit < 0) {
            throw new InvalidArgumentException('$limit must not be less than 0.');
        }

        $new = clone $this;
        $new->limit = $limit;

        return $new;
    }

    public function withSort(?Sort $sort): self
    {
        $new = clone $this;
        $new->sort = $sort;

        return $new;
    }

    public function withFilter(FilterInterface $filter): self
    {
        $new = clone $this;
        $new->_count = null;
        $new->filter = $filter;

        return $new;
    }

    public function withFilterProcessors(FilterProcessorInterface ...$filterProcessors): self
    {
        $new = clone $this;

        foreach ($filterProcessors as $filterProcessor)
        {
            if ($filterProcessor instanceof QueryProcessorInterface) {
                $new->filterProcessors[$filterProcessor->getOperator()] = $filterProcessor;
            }
        }

        return $new;
    }

    public function getSort(): ?Sort
    {
        return $this->sort;
    }

    public function read(): array
    {
        if ($this->_data === null) {
            $this->_data = $this->prepareQuery()->all();
        }

        return $this->_data;
    }

    public function readOne()
    {
        return $this->withLimit(1)->prepareQuery()->findOne();
    }
}
