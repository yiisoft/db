<?php

declare(strict_types=1);

namespace Yiisoft\Db\Query\Conditions;

use Yiisoft\Db\Expression\ExpressionBuilderInterface;
use Yiisoft\Db\Expression\ExpressionBuilderTrait;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Query\Query;

/**
 * Class InConditionBuilder builds objects of {@see InCondition}.
 */
class InConditionBuilder implements ExpressionBuilderInterface
{
    use ExpressionBuilderTrait;

    /**
     * Method builds the raw SQL from the $expression that will not be additionally escaped or quoted.
     *
     * @param ExpressionInterface|InCondition $expression the expression to be built.
     * @param array $params the binding parameters.
     *
     * @return string the raw SQL that will not be additionally escaped or quoted.
     */
    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        $operator = $expression->getOperator();
        $column = $expression->getColumn();
        $values = $expression->getValues();

        if ($column === []) {
            // no columns to test against
            return $operator === 'IN' ? '0=1' : '';
        }

        if ($values instanceof Query) {
            return $this->buildSubqueryInCondition($operator, $column, $values, $params);
        }

        if (!\is_array($values) && !$values instanceof \Traversable) {
            // ensure values is an array
            $values = (array) $values;
        }
        if ($column instanceof \Traversable || ((\is_array($column) || $column instanceof \Countable) && count($column) > 1)) {
            return $this->buildCompositeInCondition($operator, $column, $values, $params);
        }

        if (\is_array($column)) {
            $column = reset($column);
        }

        $sqlValues = $this->buildValues($expression, $values, $params);
        if (empty($sqlValues)) {
            return $operator === 'IN' ? '0=1' : '';
        }

        if (strpos($column, '(') === false) {
            $column = $this->queryBuilder->getDb()->quoteColumnName($column);
        }
        if (count($sqlValues) > 1) {
            return "$column $operator (" . implode(', ', $sqlValues) . ')';
        }

        $operator = $operator === 'IN' ? '=' : '<>';

        return $column . $operator . reset($sqlValues);
    }

    /**
     * Builds $values to be used in {@see InCondition}.
     *
     * @param ConditionInterface|InCondition $condition
     * @param array $values
     * @param array $params the binding parameters
     *
     * @return array of prepared for SQL placeholders
     */
    protected function buildValues(ConditionInterface $condition, $values, array &$params): array
    {
        $sqlValues = [];
        $column = $condition->getColumn();

        foreach ($values as $i => $value) {
            if (\is_array($value) || $value instanceof \ArrayAccess) {
                $value = $value[$column] ?? null;
            }
            if ($value === null) {
                $sqlValues[$i] = 'NULL';
            } elseif ($value instanceof ExpressionInterface) {
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
     * @param string $operator
     * @param array|string $columns
     * @param Query $values
     * @param array $params
     *
     * @return string SQL
     */
    protected function buildSubqueryInCondition(string $operator, $columns, Query $values, array &$params): string
    {
        $sql = $this->queryBuilder->buildExpression($values, $params);

        if (\is_array($columns)) {
            foreach ($columns as $i => $col) {
                if (strpos($col, '(') === false) {
                    $columns[$i] = $this->queryBuilder->getDb()->quoteColumnName($col);
                }
            }

            return '(' . implode(', ', $columns) . ") $operator $sql";
        }

        if (strpos($columns, '(') === false) {
            $columns = $this->queryBuilder->getDb()->quoteColumnName($columns);
        }

        return "$columns $operator $sql";
    }

    /**
     * Builds SQL for IN condition.
     *
     * @param string $operator
     * @param array|\Traversable $columns
     * @param array $values
     * @param array $params
     *
     * @return string SQL
     */
    protected function buildCompositeInCondition(?string $operator, $columns, $values, &$params): string
    {
        $vss = [];
        foreach ($values as $value) {
            $vs = [];
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
        foreach ($columns as $i => $column) {
            $sqlColumns[] = strpos($column, '(') === false
            ? $this->queryBuilder->getDb()->quoteColumnName($column) : $column;
        }

        return '(' . implode(', ', $sqlColumns) . ") $operator (" . implode(', ', $vss) . ')';
    }
}
