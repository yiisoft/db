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
use Yiisoft\Db\QueryBuilder\Condition\ConditionInterface;
use Yiisoft\Db\QueryBuilder\Condition\Equals;
use Yiisoft\Db\QueryBuilder\Condition\Exists;
use Yiisoft\Db\QueryBuilder\Condition\GreaterThan;
use Yiisoft\Db\QueryBuilder\Condition\GreaterThanOrEqual;
use Yiisoft\Db\QueryBuilder\Condition\In;
use Yiisoft\Db\QueryBuilder\Condition\LessThan;
use Yiisoft\Db\QueryBuilder\Condition\LessThanOrEqual;
use Yiisoft\Db\QueryBuilder\Condition\Like;
use Yiisoft\Db\QueryBuilder\Condition\Not;
use Yiisoft\Db\QueryBuilder\Condition\NotBetween;
use Yiisoft\Db\QueryBuilder\Condition\NotEquals;
use Yiisoft\Db\QueryBuilder\Condition\NotExists;
use Yiisoft\Db\QueryBuilder\Condition\NotIn;
use Yiisoft\Db\QueryBuilder\Condition\NotLike;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;

use function is_array;

/**
 * Build an object of {@see Not} into SQL expressions.
 *
 * @implements ExpressionBuilderInterface<Not>
 */
class NotBuilder implements ExpressionBuilderInterface
{
    public function __construct(private readonly QueryBuilderInterface $queryBuilder)
    {
    }

    /**
     * Build SQL for {@see Not}.
     *
     * @param Not $expression
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        $condition = is_array($expression->condition)
            ? $this->queryBuilder->createConditionFromArray($expression->condition)
            : $expression->condition;

        if ($condition === null || $condition === '') {
            return '';
        }

        if ($condition instanceof ConditionInterface) {
            $negatedCondition = $this->createNegatedCondition($condition);
            if ($negatedCondition !== null) {
                return $this->queryBuilder->buildCondition($negatedCondition, $params);
            }
        }

        $sql = $this->queryBuilder->buildCondition($condition, $params);
        return $sql === '' ? '' : "NOT ($sql)";
    }

    protected function createNegatedCondition(ConditionInterface $condition): array|string|ExpressionInterface|null
    {
        return match ($condition::class) {
            LessThan::class => new GreaterThanOrEqual($condition->column, $condition->value),
            LessThanOrEqual::class => new GreaterThan($condition->column, $condition->value),
            GreaterThan::class => new LessThanOrEqual($condition->column, $condition->value),
            GreaterThanOrEqual::class => new LessThan($condition->column, $condition->value),
            In::class => new NotIn($condition->column, $condition->values),
            NotIn::class => new In($condition->column, $condition->values),
            Between::class => new NotBetween(
                $condition->column,
                $condition->intervalStart,
                $condition->intervalEnd,
            ),
            NotBetween::class => new Between(
                $condition->column,
                $condition->intervalStart,
                $condition->intervalEnd,
            ),
            Equals::class => new NotEquals($condition->column, $condition->value),
            NotEquals::class => new Equals($condition->column, $condition->value),
            Exists::class => new NotExists($condition->query),
            NotExists::class => new Exists($condition->query),
            Like::class => new NotLike(
                $condition->column,
                $condition->value,
                $condition->caseSensitive,
                $condition->escape,
                $condition->mode,
                $condition->conjunction,
            ),
            NotLike::class => new Like(
                $condition->column,
                $condition->value,
                $condition->caseSensitive,
                $condition->escape,
                $condition->mode,
                $condition->conjunction,
            ),
            Not::class => $condition->condition,
            default => null,
        };
    }
}
