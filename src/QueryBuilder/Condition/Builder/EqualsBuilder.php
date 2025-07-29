<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Condition\Builder;

use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\ExpressionBuilderInterface;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\QueryBuilder\Condition\Equals;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;

/**
 * Build an object of {@see Equals} into SQL expressions.
 *
 * @implements ExpressionBuilderInterface<Equals>
 */
class EqualsBuilder implements ExpressionBuilderInterface
{
    public function __construct(
        private readonly QueryBuilderInterface $queryBuilder,
    ) {
    }

    /**
     * Build SQL for {@see Equals}.
     *
     * @param Equals $expression
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        $column = $this->prepareColumn($expression->column, $params);
        $value = $this->prepareValue($expression->value, $params);

        if ($value === null) {
            return $column . ' IS NULL';
        }

        return $column . '=' . $value;
    }

    /**
     * @throws InvalidConfigException
     * @throws NotSupportedException
     * @throws Exception
     */
    private function prepareColumn(string|ExpressionInterface $column, array &$params): string
    {
        if ($column instanceof ExpressionInterface) {
            return $this->queryBuilder->buildExpression($column, $params);
        }

        return $this->queryBuilder->getQuoter()->quoteColumnName($column);
    }

    /**
     * @throws InvalidConfigException
     * @throws NotSupportedException
     * @throws Exception
     */
    private function prepareValue(mixed $value, array &$params): string|null
    {
        if ($value === null) {
            return null;
        }

        return $this->queryBuilder->buildValue($value, $params);
    }
}
