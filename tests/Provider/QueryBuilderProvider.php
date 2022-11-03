<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Provider;

use Yiisoft\Db\Tests\Support\Mock;

final class QueryBuilderProvider
{
    public function batchInsert(): array
    {
        $baseQueryBuilderProvider = new BaseQueryBuilderProvider(new Mock());

        return $baseQueryBuilderProvider->batchInsert();
    }

    public function buildConditions(): array
    {
        $baseQueryBuilderProvider = new BaseQueryBuilderProvider(new Mock());

        return $baseQueryBuilderProvider->buildConditions();
    }

    public function buildFilterCondition(): array
    {
        $baseQueryBuilderProvider = new BaseQueryBuilderProvider(new Mock());

        return $baseQueryBuilderProvider->buildFilterCondition();
    }

    public function buildFrom(): array
    {
        $baseQueryBuilderProvider = new BaseQueryBuilderProvider(new Mock());

        return $baseQueryBuilderProvider->buildFrom();
    }

    public function buildWhereExists(): array
    {
        $baseQueryBuilderProvider = new BaseQueryBuilderProvider(new Mock());

        return $baseQueryBuilderProvider->buildWhereExists();
    }
}
