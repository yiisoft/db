<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Condition\Builder;

use Yiisoft\Db\Exception\Exception;
use InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\ExpressionBuilderInterface;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\QueryBuilder\Condition\AndCondition;
use Yiisoft\Db\QueryBuilder\Condition\OrCondition;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;

use function count;
use function implode;
use function is_array;
use function reset;

/**
 * Build an object of {@see AndCondition} or {@see OrCondition} into SQL expressions.
 *
 * @implements ExpressionBuilderInterface<AndCondition|OrCondition>
 */
final class LogicalConditionBuilder implements ExpressionBuilderInterface
{
    public function __construct(
        private readonly QueryBuilderInterface $queryBuilder
    ) {
    }

    /**
     * Build SQL for {@see AndCondition} or {@see OrCondition}.
     *
     * @param AndCondition|OrCondition $expression
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        $parts = $this->buildExpressions($expression->expressions, $params);

        if (empty($parts)) {
            return '';
        }

        if (count($parts) === 1) {
            return (string) reset($parts);
        }

        $operator = match ($expression::class) {
            AndCondition::class => 'AND',
            OrCondition::class => 'OR',
        };

        return '(' . implode(") $operator (", $parts) . ')';
    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     *
     * @psalm-param array<array|ExpressionInterface|scalar> $expressions
     * @psalm-return list<scalar>
     */
    private function buildExpressions(array $expressions, array &$params = []): array
    {
        $parts = [];

        foreach ($expressions as $conditionValue) {
            if (is_array($conditionValue)) {
                $conditionValue = $this->queryBuilder->buildCondition($conditionValue, $params);
            }

            if ($conditionValue instanceof ExpressionInterface) {
                $conditionValue = $this->queryBuilder->buildExpression($conditionValue, $params);
            }

            if ($conditionValue !== '') {
                $parts[] = $conditionValue;
            }
        }

        return $parts;
    }
}
