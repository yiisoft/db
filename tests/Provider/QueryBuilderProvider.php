<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Provider;

use Yiisoft\Db\Tests\Support\TestTrait;

final class QueryBuilderProvider
{
    use TestTrait;

    public function buildFrom(): array
    {
        $baseQueryBuilderProvider = new BaseQueryBuilderProvider();

        return $baseQueryBuilderProvider->buildFrom($this->getConnection());
    }
}
