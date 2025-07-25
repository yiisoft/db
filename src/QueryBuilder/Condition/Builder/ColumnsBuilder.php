<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Condition\Builder;

use Yiisoft\Db\Exception\Exception;
use InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\ExpressionBuilderInterface;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\QueryBuilder\Condition\Columns;
use Yiisoft\Db\QueryBuilder\Condition\In;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;
use Yiisoft\Db\Query\QueryInterface;

use function count;
use function implode;
use function is_iterable;

/**
 * Build an object of {@see Columns} into SQL expressions.
 *
 * @implements ExpressionBuilderInterface<Columns>
 */
class ColumnsBuilder implements ExpressionBuilderInterface
{
    public function __construct(
        private readonly QueryBuilderInterface $queryBuilder,
    ) {
    }

    /**
     * Build SQL for {@see Columns}.
     *
     * @param Columns $expression
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        $parts = [];
        foreach ($expression->values as $column => $value) {
            if (is_iterable($value) || $value instanceof QueryInterface) {
                /** IN condition */
                $parts[] = $this->queryBuilder->buildCondition(new In($column, 'IN', $value), $params);
            } else {
                $column = $this->queryBuilder->getQuoter()->quoteColumnName($column);

                $parts[] = $value === null
                    ? "$column IS NULL"
                    : $column . '=' . $this->queryBuilder->buildValue($value, $params);
            }
        }

        return (count($parts) === 1) ? $parts[0] : ('(' . implode(') AND (', $parts) . ')');
    }
}
