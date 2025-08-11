<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Condition\Builder;

use Yiisoft\Db\Exception\Exception;
use InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\ExpressionBuilderInterface;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\QueryBuilder\Condition\Between;
use Yiisoft\Db\QueryBuilder\Condition\NotBetween;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;

use function str_contains;

/**
 * Build an object of {@see Between} or {@see NotBetween} into SQL expressions.
 *
 * @implements ExpressionBuilderInterface<Between|NotBetween>
 */
class BetweenBuilder implements ExpressionBuilderInterface
{
    public function __construct(private readonly QueryBuilderInterface $queryBuilder)
    {
    }

    /**
     * Build SQL for {@see Between} or {@see NotBetween}.
     *
     * @param Between|NotBetween $expression
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        $operator = match ($expression::class) {
            Between::class => 'BETWEEN',
            NotBetween::class => 'NOT BETWEEN',
        };
        $column = $expression->column instanceof ExpressionInterface
            ? $this->queryBuilder->buildExpression($expression->column, $params)
            : $this->queryBuilder->getQuoter()->quoteColumnName($expression->column);

        $phName1 = $this->createPlaceholder($expression->intervalStart, $params);
        $phName2 = $this->createPlaceholder($expression->intervalEnd, $params);

        return "$column $operator $phName1 AND $phName2";
    }

    /**
     * Attaches `$value` to `$params` array and return placeholder.
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    protected function createPlaceholder(mixed $value, array &$params): string
    {
        if ($value instanceof ExpressionInterface) {
            return $this->queryBuilder->buildExpression($value, $params);
        }

        return $this->queryBuilder->bindParam($value, $params);
    }
}
