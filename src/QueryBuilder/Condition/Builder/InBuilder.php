<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Condition\Builder;

use ArrayAccess;
use Iterator;
use Traversable;
use Yiisoft\Db\Exception\Exception;
use InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\ExpressionBuilderInterface;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\QueryBuilder\Condition\In;
use Yiisoft\Db\QueryBuilder\Condition\NotIn;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;
use Yiisoft\Db\Query\QueryInterface;

use function array_merge;
use function array_values;
use function count;
use function implode;
use function in_array;
use function is_array;
use function is_string;
use function iterator_count;
use function reset;
use function sprintf;
use function str_contains;

/**
 * Build an object of {@see In} or {@see NotIn} into SQL expressions.
 *
 * @implements ExpressionBuilderInterface<In|NotIn>
 */
class InBuilder implements ExpressionBuilderInterface
{
    public function __construct(protected QueryBuilderInterface $queryBuilder)
    {
    }

    /**
     * Build SQL for {@see In} or {@see NotIn}.
     *
     * @param In|NotIn $expression
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        $column = $expression->column instanceof Traversable
            ? iterator_to_array($expression->column)
            : $expression->column;
        $operator = match ($expression::class) {
            In::class => 'IN',
            NotIn::class => 'NOT IN',
        };
        $values = $expression->values;

        if ($column === []) {
            /** no columns to test against */
            return $operator === 'IN' ? '0=1' : '';
        }

        if ($column instanceof ExpressionInterface) {
            $column = $this->queryBuilder->buildExpression($column);
        }

        if ($values instanceof QueryInterface) {
            return $this->buildSubqueryInCondition($operator, $column, $values, $params);
        }

        if (is_array($column)) {
            if (count($column) > 1) {
                return $this->buildCompositeInCondition($operator, $column, $values, $params);
            }
            $column = reset($column);
            if ($column instanceof ExpressionInterface) {
                $column = $this->queryBuilder->buildExpression($column);
            }
        }

        $rawValues = is_array($values)
            ? $values
            : $this->getRawValuesFromTraversableObject($values);

        $nullCondition = null;
        $nullConditionOperator = null;
        if (in_array(null, $rawValues, true)) {
            $nullCondition = $this->getNullCondition($operator, $column);
            $nullConditionOperator = $operator === 'IN' ? 'OR' : 'AND';
        }

        $sqlValues = $this->buildValues($column, $values, $params);

        if (empty($sqlValues)) {
            return $nullCondition ?? ($operator === 'IN' ? '0=1' : '');
        }

        if (!str_contains($column, '(')) {
            $column = $this->queryBuilder->getQuoter()->quoteColumnName($column);
        }

        if (count($sqlValues) > 1) {
            $sql = "$column $operator (" . implode(', ', $sqlValues) . ')';
        } else {
            $operator = $operator === 'IN' ? '=' : '<>';
            $sql = $column . $operator . reset($sqlValues);
        }

        return $nullCondition !== null && $nullConditionOperator !== null
            ? sprintf('%s %s %s', $sql, $nullConditionOperator, $nullCondition)
            : $sql;
    }

    /**
     * Builds `$values` to use in condition.
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     *
     * @psalm-return string[]
     */
    protected function buildValues(string $column, iterable $values, array &$params = []): array
    {
        $sqlValues = [];

        foreach ($values as $value) {
            if (is_array($value) || $value instanceof ArrayAccess) {
                $value = $value[$column] ?? null;
            }

            if ($value === null) {
                continue;
            }

            $sqlValues[] = $this->queryBuilder->buildValue($value, $params);
        }

        return $sqlValues;
    }

    /**
     * Build SQL for composite `IN` condition.
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     *
     * @psalm-param array<string|ExpressionInterface>|string $columns
     */
    protected function buildSubqueryInCondition(
        string $operator,
        array|string $columns,
        ExpressionInterface $values,
        array &$params = []
    ): string {
        $query = '';
        $sql = $this->queryBuilder->buildExpression($values, $params);

        if (is_array($columns)) {
            $preparedColumns = [];
            foreach ($columns as $column) {
                if ($column instanceof ExpressionInterface) {
                    $preparedColumns[] = $this->queryBuilder->buildExpression($column);
                    continue;
                }
                $preparedColumns[] = str_contains($column, '(')
                    ? $column
                    : $this->queryBuilder->getQuoter()->quoteColumnName($column);
            }
            return '(' . implode(', ', $preparedColumns) . ") $operator $sql";
        }

        if (str_contains($columns, '(')) {
            return $query;
        }

        $columns = $this->queryBuilder->getQuoter()->quoteColumnName($columns);
        return "$columns $operator $sql";
    }

    /**
     * Builds an SQL statement for checking the existence of rows with the specified composite column values.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     *
     * @psalm-param array<string|ExpressionInterface> $columns
     */
    protected function buildCompositeInCondition(
        string|null $operator,
        array $columns,
        iterable|Iterator $values,
        array &$params = []
    ): string {
        $vss = [];

        /** @psalm-var string[][] $values */
        foreach ($values as $value) {
            $vs = [];
            foreach ($columns as $column) {
                if ($column instanceof ExpressionInterface) {
                    $column = $this->queryBuilder->buildExpression($column);
                }

                $vs[] = isset($value[$column])
                    ? $this->queryBuilder->buildValue($value[$column], $params)
                    : 'NULL';
            }
            $vss[] = '(' . implode(', ', $vs) . ')';
        }

        if (empty($vss)) {
            return $operator === 'IN' ? '0=1' : '';
        }

        $sqlColumns = [];

        foreach ($columns as $column) {
            if ($column instanceof ExpressionInterface) {
                $sqlColumns[] = $this->queryBuilder->buildExpression($column);
                continue;
            }

            $sqlColumns[] = !str_contains($column, '(')
                ? $this->queryBuilder->getQuoter()->quoteColumnName($column) : $column;
        }

        return '(' . implode(', ', $sqlColumns) . ") $operator (" . implode(', ', $vss) . ')';
    }

    /**
     * The Builds are `null/is` not `null` condition for column based on the operator.
     */
    protected function getNullCondition(string $operator, string $column): string
    {
        $column = $this->queryBuilder->getQuoter()->quoteColumnName($column);

        if ($operator === 'IN') {
            return sprintf('%s IS NULL', $column);
        }

        return sprintf('%s IS NOT NULL', $column);
    }

    protected function getRawValuesFromTraversableObject(Traversable $traversableObject): array
    {
        $rawValues = [];

        /** @psalm-var mixed $value */
        foreach ($traversableObject as $value) {
            if (is_array($value)) {
                $values = array_values($value);
                $rawValues = array_merge($rawValues, $values);
            } else {
                /** @psalm-var mixed */
                $rawValues[] = $value;
            }
        }

        return $rawValues;
    }
}
