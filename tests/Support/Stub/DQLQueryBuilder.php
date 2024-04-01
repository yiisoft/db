<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Support\Stub;

use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\QueryBuilder\AbstractDQLQueryBuilder;

final class DQLQueryBuilder extends AbstractDQLQueryBuilder
{
    protected function defaultExpressionBuilders(): array
    {
        return array_merge(parent::defaultExpressionBuilders(), [
            Expression::class => ExpressionBuilder::class,
        ]);
    }
}
