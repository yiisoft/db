<?php

declare(strict_types=1);

namespace Yiisoft\Db;

use Yiisoft\Db\Contracts\ExpressionInterface;
use Yiisoft\Db\Contracts\QueryInterface;
use Yiisoft\Db\Exception\NotSupportedException;

/**
 * The BaseQuery trait represents the minimum method set of a database Query.
 *
 * It is supposed to be used in a class that implements the {@see QueryInterface}.
 */
trait QueryTrait
{
    /**
     * @var string|array query condition. This refers to the WHERE clause in a SQL statement.
     * For example, `['age' => 31, 'team' => 1]`.
     *
     * @see where() for valid syntax on specifying this value.
     */
    public $where;

    /**
     * @var int|ExpressionInterface maximum number of records to be returned. May be an instance of
     * {@see ExpressionInterface}. If not set or less than 0, it means no limit.
     */
    public $limit;

    /**
     * @var int|ExpressionInterface zero-based offset from where the records are to be returned.
     * May be an instance of {@see ExpressionInterface}. If not set or less than 0, it means starting from the
     * beginning.
     */
    public $offset;

    /**
     * @var array how to sort the query results. This is used to construct the ORDER BY clause in a SQL statement.
     * The array keys are the columns to be sorted by, and the array values are the corresponding sort directions which
     * can be either [SORT_ASC](http://php.net/manual/en/array.constants.php#constant.sort-asc)
     * or [SORT_DESC](http://php.net/manual/en/array.constants.php#constant.sort-desc).
     * The array may also contain [[ExpressionInterface]] objects. If that is the case, the expressions
     * will be converted into strings without any change.
     */
    public $orderBy = [];

    /**
     * @var string|callable the name of the column by which the query results should be indexed by.
     * This can also be a callable (e.g. anonymous function) that returns the index value based on the given
     * row data. For more details, see {@see indexBy()}. This property is only used by
     * {@see QueryInterface::all()|all()}.
     */
    public $indexBy;

    /**
     * @var bool whether to emulate the actual query execution, returning empty or false results.
     */
    public $emulateExecution = false;

    /**
     * Sets the {@see indexBy} property.
     *
     * @param string|callable $column the name of the column by which the query results should be indexed by.
     *
     * This can also be a callable (e.g. anonymous function) that returns the index value based on the given row data.
     *
     * The signature of the callable should be:
     *
     * ```php
     * function ($row)
     * {
     *     // return the index value corresponding to $row
     * }
     * ```
     *
     * @return $this the query object itself
     */
    public function indexBy($column)
    {
        $this->indexBy = $column;

        return $this;
    }

    /**
     * Sets the WHERE part of the query.
     *
     * See {@see QueryInterface::where()} for detailed documentation.
     *
     * @param array $condition the conditions that should be put in the WHERE part.
     *
     * @return $this the query object itself
     *
     * {@see andWhere()}
     * {@see orWhere()}
     */
    public function where(array $condition)
    {
        $this->where = $condition;

        return $this;
    }

    /**
     * Adds an additional WHERE condition to the existing one.
     *
     * The new condition and the existing one will be joined using the 'AND' operator.
     *
     * @param array $condition the new WHERE condition. Please refer to {@see where()} on how to specify this parameter.
     *
     * @return $this the query object itself
     *
     * {@see where()}
     * {@see orWhere()}
     */
    public function andWhere($condition)
    {
        if ($this->where === null) {
            $this->where = $condition;
        } else {
            $this->where = ['and', $this->where, $condition];
        }

        return $this;
    }

    /**
     * Adds an additional WHERE condition to the existing one.
     *
     * The new condition and the existing one will be joined using the 'OR' operator.
     *
     * @param array $condition the new WHERE condition. Please refer to {@see where()} on how to specify this parameter.
     *
     * @return $this the query object itself
     *
     * {@see where()}
     * {@see andWhere()}
     */
    public function orWhere($condition)
    {
        if ($this->where === null) {
            $this->where = $condition;
        } else {
            $this->where = ['or', $this->where, $condition];
        }

        return $this;
    }

    /**
     * Sets the WHERE part of the query but ignores [[isEmpty()|empty operands]].
     *
     * This method is similar to {@see where()}. The main difference is that this method will remove
     * {@see isEmpty()|empty query operands}. As a result, this method is best suited for building query conditions
     * based on filter values entered by users.
     *
     * The following code shows the difference between this method and {@see where()}:
     *
     * ```php
     * // WHERE `age`=:age
     * $query->filterWhere(['name' => null, 'age' => 20]);
     * // WHERE `age`=:age
     * $query->where(['age' => 20]);
     * // WHERE `name` IS NULL AND `age`=:age
     * $query->where(['name' => null, 'age' => 20]);
     * ```
     *
     * Note that unlike {@see where()}, you cannot pass binding parameters to this method.
     *
     * @param array $condition the conditions that should be put in the WHERE part.
     *
     * See {@see where()} on how to specify this parameter.
     *
     * @return $this the query object itself
     *
     * {@see where()}
     * {@see andFilterWhere()}
     * {@see orFilterWhere()}
     */
    public function filterWhere(array $condition)
    {
        $condition = $this->filterCondition($condition);

        if ($condition !== []) {
            $this->where($condition);
        }

        return $this;
    }

    /**
     * Adds an additional WHERE condition to the existing one but ignores {@see isEmpty()|empty operands}.
     *
     * The new condition and the existing one will be joined using the 'AND' operator.
     *
     * This method is similar to {@see andWhere()}. The main difference is that this method will remove
     * {@see isEmpty()|empty query operands}. As a result, this method is best suited for building query conditions
     * based on filter values entered by users.
     *
     * @param array $condition the new WHERE condition. Please refer to {@see where()} on how to specify this parameter.
     *
     * @return $this the query object itself
     *
     * {@see filterWhere()}
     * {@see orFilterWhere()}
     */
    public function andFilterWhere(array $condition)
    {
        $condition = $this->filterCondition($condition);

        if ($condition !== []) {
            $this->andWhere($condition);
        }

        return $this;
    }

    /**
     * Adds an additional WHERE condition to the existing one but ignores {@see isEmpty()|empty operands}.
     *
     * The new condition and the existing one will be joined using the 'OR' operator.
     *
     * This method is similar to {@see orWhere()}. The main difference is that this method will remove
     * {@see isEmpty()|empty query operands}. As a result, this method is best suited for building query conditions
     * based on filter values entered by users.
     *
     * @param array $condition the new WHERE condition. Please refer to {@see where()} on how to specify this parameter.
     *
     * @return $this the query object itself
     *
     * {@see filterWhere()}
     * {@see andFilterWhere()}
     */
    public function orFilterWhere(array $condition)
    {
        $condition = $this->filterCondition($condition);

        if ($condition !== []) {
            $this->orWhere($condition);
        }

        return $this;
    }

    /**
     * Removes {@see isEmpty()|empty operands} from the given query condition.
     *
     * @param array $condition the original condition
     *
     * @throws NotSupportedException if the condition operator is not supported
     *
     * @return array the condition with {@see isEmpty()|empty operands} removed.
     */
    protected function filterCondition($condition)
    {
        if (!is_array($condition)) {
            return $condition;
        }

        if (!isset($condition[0])) {
            // hash format: 'column1' => 'value1', 'column2' => 'value2', ...
            foreach ($condition as $name => $value) {
                if ($this->isEmpty($value)) {
                    unset($condition[$name]);
                }
            }

            return $condition;
        }

        // operator format: operator, operand 1, operand 2, ...

        $operator = array_shift($condition);

        switch (strtoupper($operator)) {
            case 'NOT':
            case 'AND':
            case 'OR':
                foreach ($condition as $i => $operand) {
                    $subCondition = $this->filterCondition($operand);
                    if ($this->isEmpty($subCondition)) {
                        unset($condition[$i]);
                    } else {
                        $condition[$i] = $subCondition;
                    }
                }

                if (empty($condition)) {
                    return [];
                }

                break;
            case 'BETWEEN':
            case 'NOT BETWEEN':
                if (array_key_exists(1, $condition) && array_key_exists(2, $condition)) {
                    if ($this->isEmpty($condition[1]) || $this->isEmpty($condition[2])) {
                        return [];
                    }
                }

                break;
            default:
                if (array_key_exists(1, $condition) && $this->isEmpty($condition[1])) {
                    return [];
                }
        }

        array_unshift($condition, $operator);

        return $condition;
    }

    /**
     * Returns a value indicating whether the give value is "empty".
     *
     * The value is considered "empty", if one of the following conditions is satisfied:
     *
     * - it is `null`,
     * - an empty string (`''`),
     * - a string containing only whitespace characters,
     * - or an empty array.
     *
     * @param mixed $value
     *
     * @return bool if the value is empty
     */
    protected function isEmpty($value)
    {
        return $value === '' || $value === [] || $value === null || is_string($value) && trim($value) === '';
    }

    /**
     * Sets the ORDER BY part of the query.
     *
     * @param string|array|ExpressionInterface $columns the columns (and the directions) to be ordered by.
     *
     * Columns can be specified in either a string (e.g. `"id ASC, name DESC"`) or an array
     * (e.g. `['id' => SORT_ASC, 'name' => SORT_DESC]`).
     *
     * The method will automatically quote the column names unless a column contains some parenthesis
     * (which means the column contains a DB expression).
     *
     * Note that if your order-by is an expression containing commas, you should always use an array
     * to represent the order-by information. Otherwise, the method will not be able to correctly determine
     * the order-by columns.
     *
     * Since {@see ExpressionInterface} object can be passed to specify the ORDER BY part explicitly in plain SQL.
     *
     * @return $this the query object itself
     *
     * {@see addOrderBy()}
     */
    public function orderBy($columns)
    {
        $this->orderBy = $this->normalizeOrderBy($columns);

        return $this;
    }

    /**
     * Adds additional ORDER BY columns to the query.
     *
     * @param string|array|ExpressionInterface $columns the columns (and the directions) to be ordered by.
     * Columns can be specified in either a string (e.g. "id ASC, name DESC") or an array
     * (e.g. `['id' => SORT_ASC, 'name' => SORT_DESC]`).
     *
     * The method will automatically quote the column names unless a column contains some parenthesis
     * (which means the column contains a DB expression).
     *
     * Note that if your order-by is an expression containing commas, you should always use an array
     * to represent the order-by information. Otherwise, the method will not be able to correctly determine
     * the order-by columns.
     *
     * Since {@see ExpressionInterface} object can be passed to specify the ORDER BY part explicitly in plain SQL.
     *
     * @return $this the query object itself
     *
     * {@see orderBy()}
     */
    public function addOrderBy($columns)
    {
        $columns = $this->normalizeOrderBy($columns);
        if ($this->orderBy === null) {
            $this->orderBy = $columns;
        } else {
            $this->orderBy = array_merge($this->orderBy, $columns);
        }

        return $this;
    }

    /**
     * Normalizes format of ORDER BY data.
     *
     * @param array|string|ExpressionInterface $columns the columns value to normalize.
     *
     * See {@see orderBy} and {@see addOrderBy}.
     *
     * @return array
     */
    protected function normalizeOrderBy($columns)
    {
        if ($columns instanceof ExpressionInterface) {
            return [$columns];
        } elseif (is_array($columns)) {
            return $columns;
        }

        $columns = preg_split('/\s*,\s*/', trim($columns), -1, PREG_SPLIT_NO_EMPTY);
        $result = [];
        foreach ($columns as $column) {
            if (preg_match('/^(.*?)\s+(asc|desc)$/i', $column, $matches)) {
                $result[$matches[1]] = strcasecmp($matches[2], 'desc') ? SORT_ASC : SORT_DESC;
            } else {
                $result[$column] = SORT_ASC;
            }
        }

        return $result;
    }

    /**
     * Sets the LIMIT part of the query.
     *
     * @param int|ExpressionInterface|null $limit the limit. Use null or negative value to disable limit.
     *
     * @return $this the query object itself
     */
    public function limit($limit)
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * Sets the OFFSET part of the query.
     *
     * @param int|ExpressionInterface|null $offset the offset. Use null or negative value to disable offset.
     *
     * @return $this the query object itself
     */
    public function offset($offset)
    {
        $this->offset = $offset;

        return $this;
    }

    /**
     * Sets whether to emulate query execution, preventing any interaction with data storage.
     *
     * After this mode is enabled, methods, returning query results like {@see QueryInterface::one()},
     * {@see QueryInterface::all()}, {@see QueryInterface::exists()} and so on, will return empty or false values.
     * You should use this method in case your program logic indicates query should not return any results, like
     * in case you set false where condition like `0=1`.
     *
     * @param bool $value whether to prevent query execution.
     *
     * @return $this the query object itself.
     */
    public function emulateExecution($value = true)
    {
        $this->emulateExecution = $value;

        return $this;
    }

    /**
     * Configures an object with the given configuration.
     *
     * @param object $object the object to be configured
     * @param iterable $config property values and methods to call
     *
     * @return object the object itself
     */
    public function configure($object, iterable $config)
    {
        foreach ($config as $action => $arguments) {
            if (substr($action, -2) === '()') {
                // method call
                \call_user_func_array([$object, substr($action, 0, -2)], $arguments);
            } else {
                // property
                $object->$action = $arguments;
            }
        }

        return $object;
    }

    public function setWhere($value): void
    {
        $this->where = $value;
    }

    public function setLimit($value): void
    {
        $this->limit = $value;
    }

    public function setOffset($value): void
    {
        $this->offset = $value;
    }

    public function setOrderBy(?array $value): void
    {
        $this->orderBy = $value;
    }

    public function setIndexBy($value): void
    {
        $this->indexBy = $value;
    }
}
