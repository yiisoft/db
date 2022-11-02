<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Oracle\Provider;

use Yiisoft\Db\Tests\Support\DbHelper;
use Yiisoft\Db\Tests\Support\TraversableObject;

final class QueryBuilderProvider extends \Yiisoft\Db\Tests\Provider\QueryBuilderProvider
{
    public function buildConditions(): array
    {
        $conditions = parent::buildConditions();

        /* adjust dbms specific escaping */
        foreach ($conditions as $i => $condition) {
            $conditions[$i][1] = DbHelper::replaceQuotes($condition[1], 'oci');
        }

        return $conditions;
    }
}
