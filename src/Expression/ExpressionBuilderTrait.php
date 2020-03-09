<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression;

use Yiisoft\Db\Query\QueryBuilder;

/**
 * Trait ExpressionBuilderTrait provides common constructor for classes that should implement
 * {@see ExpressionBuilderInterface}.
 */
trait ExpressionBuilderTrait
{
    protected QueryBuilder $queryBuilder;

    public function __construct(QueryBuilder $queryBuilder)
    {
        $this->queryBuilder = $queryBuilder;
    }
}
