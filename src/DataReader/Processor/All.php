<?php

declare(strict_types=1);

namespace Yiisoft\Db\Processor;

use Yiisoft\Data\Reader\Filter\All as FilterAll;
use Yiisoft\Data\Reader\Filter\FilterInterface;
use Yiisoft\Db\Query\Query;



class All implements QueryProcessorInterface
{
    public function getOperator(): string
    {
        return FilterAll::getOperator();
    }

    public function apply(Query $query, FilterInterface $filter): Query
    {
        return $query->andWhere($filter->toArray());
    }
}
