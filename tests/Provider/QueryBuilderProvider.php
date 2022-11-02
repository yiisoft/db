<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Provider;

use Yiisoft\Db\Tests\Support\Mock;

final class QueryBuilderProvider
{
    public function buildConditions(): array
    {
        $baseQueryBuilderProvider = new BaseQueryBuilderProvider(new Mock('sqlite'));

        return $baseQueryBuilderProvider->buildConditions();
    }

    public function buildFilterCondition(): array
    {
        $baseQueryBuilderProvider = new BaseQueryBuilderProvider(new Mock('sqlite'));

        return $baseQueryBuilderProvider->buildFilterCondition();
    }

    public function buildWhereExists(): array
    {
        $baseQueryBuilderProvider = new BaseQueryBuilderProvider(new Mock('sqlite'));

        return $baseQueryBuilderProvider->buildWhereExists();
    }
}
