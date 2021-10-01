<?php

declare(strict_types=1);

namespace Yiisoft\Db\DataReader\Filter;

use Yiisoft\Data\Reader\Filter\Equals as FilterEquals;

/**
 *
 */
class Equals extends CompareFilter
{
    public static function getOperator(): string
    {
        return FilterEquals::getOperator();
    }

    public function toArray(): array
    {
        if ($this->_value === null && $this->_ignoreNull) {
            return [];
        }

        return parent::toArray();
    }
}
