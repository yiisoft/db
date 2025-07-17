<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Condition\Builder;

use Yiisoft\Db\Exception\Exception;
use InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\ExpressionBuilderInterface;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\QueryBuilder\Condition\Interface\HashConditionInterface;
use Yiisoft\Db\QueryBuilder\Condition\InCondition;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;
use Yiisoft\Db\Query\QueryInterface;

use function count;
use function implode;
use function is_iterable;
use function str_contains;

/**
 * Build an object of {@see HashConditionInterface} into SQL expressions.
 *
 * @implements ExpressionBuilderInterface<HashConditionInterface>
 */
class HashConditionBuilder implements ExpressionBuilderInterface
{
    public function __construct(private readonly QueryBuilderInterface $queryBuilder)
    {
    }

    /**
     * Build SQL for {@see HashConditionInterface}.
     *
     * @param HashConditionInterface $expression
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        $hash = $expression->getHash() ?? [];
        $parts = [];

        /**
         * @psalm-var array<string, array|mixed> $hash
         */
        foreach ($hash as $column => $value) {
            if (is_iterable($value) || $value instanceof QueryInterface) {
                /** IN condition */
                $parts[] = $this->queryBuilder->buildCondition(new InCondition($column, 'IN', $value), $params);
            } else {
                if (!str_contains($column, '(')) {
                    $column = $this->queryBuilder->getQuoter()->quoteColumnName($column);
                }

                $parts[] = match (true) {
                    $value === null => "$column IS NULL",
                    $value instanceof ExpressionInterface => "$column=" . $this->queryBuilder->buildExpression($value, $params),
                    default => $column . '=' . $this->queryBuilder->buildValue($value, $params),
                };
            }
        }

        return (count($parts) === 1) ? $parts[0] : ('(' . implode(') AND (', $parts) . ')');
    }
}
