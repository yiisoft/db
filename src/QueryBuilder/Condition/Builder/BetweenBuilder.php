<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Condition\Builder;

use Yiisoft\Db\Exception\Exception;
use InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\ExpressionBuilderInterface;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\QueryBuilder\Condition\AbstractBetween;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;

use function str_contains;

/**
 * Build an object of {@see Between} into SQL expressions.
 *
 * @implements ExpressionBuilderInterface<AbstractBetween>
 */
class BetweenBuilder implements ExpressionBuilderInterface
{
    public function __construct(private readonly QueryBuilderInterface $queryBuilder)
    {
    }

    /**
     * Build SQL for {@see AbstractBetween}.
     *
     * @param AbstractBetween $expression
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        $operator = $expression->isNot() ? 'NOT BETWEEN' : 'BETWEEN';
        $column = $expression->column;
        $column = $column instanceof ExpressionInterface
            ? $this->queryBuilder->buildExpression($column)
            : $column;

        if (!str_contains($column, '(')) {
            $column = $this->queryBuilder->getQuoter()->quoteColumnName($column);
        }

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
