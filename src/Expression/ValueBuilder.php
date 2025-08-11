<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression;

use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;

/**
 * @implements ExpressionBuilderInterface<Value>
 */
final class ValueBuilder implements ExpressionBuilderInterface
{
    public function __construct(
        private readonly QueryBuilderInterface $queryBuilder,
    ) {
    }

    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        return $this->queryBuilder->buildValue($expression->value, $params);
    }
}
