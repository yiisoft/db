<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db;

use Yiisoft\Db\Tests\Provider\QueryBuilderProvider as BaseQueryBuilderProvider;

class QueryBuilderProvider extends BaseQueryBuilderProvider
{
    public static function batchInsert(): array
    {
        $result = parent::batchInsert();

        $result['customer3']['expected'] = 'INSERT INTO [customer] VALUES (:qp0)';
        $result['customer3']['expectedParams'] = [':qp0' => 'no columns passed'];

        return $result;
    }
}
