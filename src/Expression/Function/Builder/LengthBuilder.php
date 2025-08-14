<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression\Function\Builder;

use Yiisoft\Db\Expression\Builder\ExpressionBuilderInterface;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Expression\Function\Length;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;

use function is_string;

/**
 * Builds SQL LENGTH() function expressions for {@see Length} objects.
 *
 * @implements ExpressionBuilderInterface<Length>
 */
final class LengthBuilder implements ExpressionBuilderInterface
{
    public function __construct(private readonly QueryBuilderInterface $queryBuilder)
    {
    }

    /**
     * Builds a SQL LENGTH() function expression from the given {@see Length} object.
     *
     * @param Length $expression The expression to build.
     * @param array $params The parameters to be bound to the query.
     *
     * @return string The SQL LENGTH() function expression.
     */
    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        $operand = $expression->operand;

        if (is_string($operand)) {
            return "LENGTH($operand)";
        }

        return 'LENGTH(' . $this->queryBuilder->buildExpression($operand, $params) . ')';
    }
}
