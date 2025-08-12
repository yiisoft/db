<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression\Builder;

use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Expression\Value;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;

/**
 * Builder for {@see Value} expressions that converts values into SQL parameters.
 *
 * This builder takes {@see Value} expressions and converts them into properly formatted SQL parameter placeholders
 * while adding the actual values to the parameters array for safe binding during query execution.
 *
 * @implements ExpressionBuilderInterface<Value>
 */
final class ValueBuilder implements ExpressionBuilderInterface
{
    /**
     * @param QueryBuilderInterface $queryBuilder The query builder instance.
     */
    public function __construct(
        private readonly QueryBuilderInterface $queryBuilder,
    ) {
    }

    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        return $this->queryBuilder->buildValue($expression->value, $params);
    }
}
