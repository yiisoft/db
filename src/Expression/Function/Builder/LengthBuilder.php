<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression\Function\Builder;

use Yiisoft\Db\Expression\ExpressionBuilderInterface;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Expression\Function\Length;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;

use function is_string;

/**
 * Builds SQL `LENGTH()` function expressions for {@see Length} objects.
 *
 * @implements ExpressionBuilderInterface<Length>
 */
final class LengthBuilder implements ExpressionBuilderInterface
{
    public function __construct(private readonly QueryBuilderInterface $queryBuilder)
    {
    }

    /**
     * Builds a SQL `LENGTH()` function expression from the given {@see Length} object.
     *
     * @param Length $expression The expression to build.
     * @param array $params The parameters to be bound to the query.
     *
     * @return string The SQL `LENGTH()` function expression.
     */
    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        return 'LENGTH(' . $this->buildOperand($expression->operand, $params) . ')';
    }

    /**
     * Builds an operand expression.
     */
    private function buildOperand(mixed $operand, array &$params): string
    {
        if (is_string($operand)) {
            return $this->queryBuilder->getQuoter()->quoteColumnName($operand);
        }

        return $this->queryBuilder->buildValue($operand, $params);
    }
}
