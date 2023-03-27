<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Condition\Builder;

use ArrayAccess;
use Iterator;
use Traversable;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\ExpressionBuilderInterface;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\QueryBuilder\Condition\InCondition;
use Yiisoft\Db\QueryBuilder\Condition\Interface\InConditionInterface;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;
use Yiisoft\Db\Query\QueryInterface;

use function array_merge;
use function array_values;
use function count;
use function implode;
use function in_array;
use function is_array;
use function iterator_count;
use function reset;
use function sprintf;
use function str_contains;
use function strtoupper;

/**
 * Build an object of {@see InCondition} into SQL expressions.
 */
class InConditionBuilder implements ExpressionBuilderInterface
{
    public function __construct(protected QueryBuilderInterface $queryBuilder)
    {
    }

    /**
     * Build SQL for {@see InCondition}.
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function build(InConditionInterface $expression, array &$params = []): string
    {
        $column = $expression->getColumn();
        $operator = strtoupper($expression->getOperator());
        $values = $expression->getValues();

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

        if (!is_array($values) && !is_iterable($values)) {
            /** ensure values is an array */
            $values = (array) $values;
        }

        if (is_array($column)) {
            if (count($column) > 1) {
                return $this->buildCompositeInCondition($operator, $column, $values, $params);
            }

            /** @psalm-var mixed $column */
            $column = reset($column);
        }

        if ($column instanceof Iterator) {
            if (iterator_count($column) > 1) {
                return $this->buildCompositeInCondition($operator, $column, $values, $params);
            }

            $column->rewind();
            /** @psalm-var mixed $column */
            $column = $column->current();
        }

        if (is_array($values)) {
            $rawValues = $values;
        } else {
            $rawValues = $this->getRawValuesFromTraversableObject($values);
        }

        $nullCondition = null;
        $nullConditionOperator = null;
        if (is_string($column) && in_array(null, $rawValues, true)) {
            $nullCondition = $this->getNullCondition($operator, $column);
            $nullConditionOperator = $operator === 'IN' ? 'OR' : 'AND';
        }

        $sqlValues = $this->buildValues($expression, $values, $params);

        if (empty($sqlValues)) {
            return $nullCondition ?? ($operator === 'IN' ? '0=1' : '');
        }

        if (is_string($column) && !str_contains($column, '(')) {
            $column = $this->queryBuilder->quoter()->quoteColumnName($column);
        }

        if (count($sqlValues) > 1) {
            $sql = "$column $operator (" . implode(', ', $sqlValues) . ')';
        } else {
            $operator = $operator === 'IN' ? '=' : '<>';
            $sql = (string) $column . $operator . reset($sqlValues);
        }

        /** @var int|string|null $nullCondition */
        return $nullCondition !== null && $nullConditionOperator !== null
            ? sprintf('%s %s %s', $sql, $nullConditionOperator, $nullCondition)
            : $sql;
    }

    /**
     * Builds `$values` to use in {@see InCondition}.
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     *
     * @psalm-return string[]
     *
     * @psalm-suppress MixedArrayTypeCoercion
     * @psalm-suppress MixedArrayOffset
     */
    protected function buildValues(InConditionInterface $condition, iterable $values, array &$params = []): array
    {
        $sqlValues = [];
        $column = $condition->getColumn();

        if (is_array($column)) {
            /** @psalm-var mixed $column */
            $column = reset($column);
        }

        if ($column instanceof Iterator) {
            $column->rewind();
            /** @psalm-var mixed $column */
            $column = $column->current();
        }

        /**
         * @psalm-var string|int $i
         * @psalm-var mixed $value
         */
        foreach ($values as $i => $value) {
            if (is_array($value) || $value instanceof ArrayAccess) {
                /** @psalm-var mixed $value */
                $value = $value[$column] ?? null;
            }

            if ($value === null) {
                continue;
            }

            if ($value instanceof ExpressionInterface) {
                $sqlValues[$i] = $this->queryBuilder->buildExpression($value, $params);
            } else {
                $sqlValues[$i] = $this->queryBuilder->bindParam($value, $params);
            }
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
     */
    protected function buildSubqueryInCondition(
        string $operator,
        iterable|string|Iterator $columns,
        ExpressionInterface $values,
        array &$params = []
    ): string {
        $query = '';
        $sql = $this->queryBuilder->buildExpression($values, $params);

        if (is_array($columns)) {
            /** @psalm-var string[] $columns */
            foreach ($columns as $i => $col) {
                if ($col instanceof ExpressionInterface) {
                    $columns[$i] = $this->queryBuilder->buildExpression($col);
                    continue;
                }

                if (!str_contains($col, '(')) {
                    $columns[$i] = $this->queryBuilder->quoter()->quoteColumnName($col);
                }
            }

            $query = '(' . implode(', ', $columns) . ") $operator $sql";
        }

        if (is_string($columns) && !str_contains($columns, '(')) {
            $columns = $this->queryBuilder->quoter()->quoteColumnName($columns);
            $query = "$columns $operator $sql";
        }

        return $query;
    }

    /**
     * Builds an SQL statement for checking the existence of rows with the specified composite column values.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     */
    protected function buildCompositeInCondition(
        string|null $operator,
        iterable $columns,
        iterable|Iterator $values,
        array &$params = []
    ): string {
        $vss = [];

        /** @psalm-var string[][] $values */
        foreach ($values as $value) {
            $vs = [];
            /** @psalm-var string[] $columns */
            foreach ($columns as $column) {
                if ($column instanceof ExpressionInterface) {
                    $column = $this->queryBuilder->buildExpression($column);
                }

                if (isset($value[$column])) {
                    $vs[] = $this->queryBuilder->bindParam($value[$column], $params);
                } else {
                    $vs[] = 'NULL';
                }
            }
            $vss[] = '(' . implode(', ', $vs) . ')';
        }

        if (empty($vss)) {
            return $operator === 'IN' ? '0=1' : '';
        }

        $sqlColumns = [];

        /** @psalm-var string[] $columns */
        foreach ($columns as $column) {
            if ($column instanceof ExpressionInterface) {
                $sqlColumns[] = $this->queryBuilder->buildExpression($column);
                continue;
            }

            $sqlColumns[] = !str_contains($column, '(')
                ? $this->queryBuilder->quoter()->quoteColumnName($column) : $column;
        }

        return '(' . implode(', ', $sqlColumns) . ") $operator (" . implode(', ', $vss) . ')';
    }

    /**
     * The Builds are `null/is` not `null` condition for column based on the operator.
     */
    protected function getNullCondition(string $operator, string $column): string
    {
        $column = $this->queryBuilder->quoter()->quoteColumnName($column);

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
