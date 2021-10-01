<?php

declare(strict_types=1);

namespace Yiisoft\Db\DataReader\Filter;

use Yiisoft\Data\Reader\Filter\FilterInterface;


abstract  class GroupFilter implements FilterInterface
{
    protected array $_filters;

    public function __construct(FilterInterface ...$filters)
    {
        $this->_filters = $filters;
    }

    public function toArray(): array
    {
        $array = [static::getOperator()];

        foreach ($this->_filters as $filter)
        {
            $arr = $filter->toArray();

            if (count($arr)) {
                $array[] = $filter->toArray();
            }
        }

        return count($array) > 1 ? $array : [];
    }
}
