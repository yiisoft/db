<?php

declare(strict_types=1);

namespace Yiisoft\Db\Processor;

use Yiisoft\Data\Reader\Filter\Equals as FilterEquals;
use Yiisoft\Data\Reader\Filter\FilterInterface;
use Yiisoft\Db\Query\Query;



class Equals implements QueryProcessorInterface
{
    public function getOperator(): string
    {
        return FilterEquals::getOperator();
    }

    public function apply(Query $query, FilterInterface $filter): Query
    {
        $array = $filter->toArray();

        if (count($array) === 0) {
            return $query;
        }

        return $query->andWhere($array);
    }
}
