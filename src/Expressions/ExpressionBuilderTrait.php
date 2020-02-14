<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expressions;

use Yiisoft\Db\Querys\QueryBuilder;

/**
 * Trait ExpressionBuilderTrait provides common constructor for classes that
 * should implement {@see ExpressionBuilderInterface}.
 */
trait ExpressionBuilderTrait
{
    /**
     * @var QueryBuilder
     */
    protected QueryBuilder $queryBuilder;

    /**
     * ExpressionBuilderTrait constructor.
     *
     * @param QueryBuilder $queryBuilder
     */
    public function __construct(QueryBuilder $queryBuilder)
    {
        $this->queryBuilder = $queryBuilder;
    }
}
