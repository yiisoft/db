<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Conditions\Builder;

use ArrayAccess;
use Iterator;
use Traversable;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\ExpressionBuilderInterface;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\QueryBuilder\Conditions\InCondition;
use Yiisoft\Db\QueryBuilder\Conditions\Interface\InConditionInterface;
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
 * Class InConditionBuilder builds objects of {@see InCondition}.
 */
class InConditionBuilder implements ExpressionBuilderInterface
{
    public function __construct(private QueryBuilderInterface $queryBuilder)
    {
    }

    /**
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException
     */
    public function build(InConditionInterface $expression, array &$params = []): string
    {
        $column = $expression->getColumn();
        $nullConditionOperator = '';
        $operator = strtoupper($expression->getOperator());
        $values = $expression->getValues();

        if ($column === []) {
            /** no columns to test against */
            return $operator === 'IN' ? '0=1' : '';
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

            /** @var mixed */
            $column = reset($column);
        }

        if ($column instanceof Iterator) {
            if (iterator_count($column) > 1) {
                return $this->buildCompositeInCondition($operator, $column, $values, $params);
            }

            $column->rewind();
            /** @var mixed */
            $column = $column->current();
        }

        if (is_array($values)) {
            $rawValues = $values;
        } else {
            $rawValues = $this->getRawValuesFromTraversableObject($values);
        }

        if (is_string($column) && in_array(null, $rawValues, true)) {
            $nullCondition = $this->getNullCondition($operator, $column);
            $nullConditionOperator = $operator === 'IN' ? 'OR' : 'AND';
        }

        $sqlValues = $this->buildValues($expression, $values, $params);

        if (empty($sqlValues)) {
            return empty($nullCondition) ? ($operator === 'IN' ? '0=1' : '') : (string) $nullCondition;
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
        return isset($nullCondition) ? sprintf('%s %s %s', $sql, $nullConditionOperator, $nullCondition) : $sql;
    }

    /**
     * Builds $values to be used in {@see InCondition}.
     *
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException
     *
     * @psalm-return string[]
     *
     * @psalm-suppress MixedArrayTypeCoercion
     * @psalm-suppress MixedArrayOffset
     */
    protected function buildValues(InConditionInterface $condition, array|Traversable $values, array &$params = []): array
    {
        $sqlValues = [];
        $column = $condition->getColumn();

        if (is_array($column)) {
            /** @var mixed */
            $column = reset($column);
        }

        if ($column instanceof Iterator) {
            $column->rewind();
            /** @var mixed */
            $column = $column->current();
        }

        /** @var mixed $value */
        foreach ($values as $i => $value) {
            if (is_array($value) || $value instanceof ArrayAccess) {
                /** @var mixed */
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
     * Builds SQL for IN condition.
     *
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException
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
     * Builds SQL for IN condition.
     */
    protected function buildCompositeInCondition(
        ?string $operator,
        array|Traversable $columns,
        iterable|Iterator $values,
        array &$params = []
    ): string {
        $vss = [];

        /** @psalm-var string[][] $values */
        foreach ($values as $value) {
            $vs = [];
            /** @psalm-var string[] $columns */
            foreach ($columns as $column) {
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
            $sqlColumns[] = !str_contains($column, '(')
                ? $this->queryBuilder->quoter()->quoteColumnName($column) : $column;
        }

        return '(' . implode(', ', $sqlColumns) . ") $operator (" . implode(', ', $vss) . ')';
    }

    /**
     * Builds is null/is not null condition for column based on operator.
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

        /** @var mixed $value */
        foreach ($traversableObject as $value) {
            if (is_array($value)) {
                $values = array_values($value);
                $rawValues = array_merge($rawValues, $values);
            } else {
                /** @var mixed */
                $rawValues[] = $value;
            }
        }

        return $rawValues;
    }
}
