<?php

declare(strict_types=1);

namespace Yiisoft\Db\DataReader\Filter;

use Yiisoft\Data\Reader\Filter\All as FilterAll;



class All extends GroupFilter
{
    public static function getOperator(): string
    {
        return FilterAll::getOperator();
    }
}