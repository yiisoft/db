<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression;

use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;

use function is_string;

/**
 * Builds a SQL representation of a {@see CompositeExpression}.
 *
 * @implements ExpressionBuilderInterface<CompositeExpression>
 */
final class CompositeExpressionBuilder implements ExpressionBuilderInterface
{
    public function __construct(
        private readonly QueryBuilderInterface $queryBuilder,
    ) {
    }

    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        $parts = [];
        foreach ($expression->expressions as $e) {
            if (is_string($e)) {
                $e = new Expression($e);
            }
            $parts[] = $this->queryBuilder->buildExpression($e, $params);
        }
        return implode($expression->separator, $parts);
    }
}
