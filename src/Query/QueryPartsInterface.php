<?php

declare(strict_types=1);

namespace Yiisoft\Db\Query;

use Closure;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Expression\ExpressionInterface;

interface QueryPartsInterface
{
    /**
     * Adds additional group-by columns to the existing ones.
     *
     * @param array|ExpressionInterface|string $columns additional columns to be grouped by.
     * Columns can be specified in either a string (e.g. "id, name") or an array (e.g. ['id', 'name']).
     * The method will automatically quote the column names unless a column contains some parenthesis (which means the
     * column contains a DB expression).
     *
     * Note that if your group-by is an expression containing commas, you should always use an array to represent the
     * group-by information. Otherwise, the method will not be able to correctly determine the group-by columns.
     *
     * {@see Expression} object can be passed to specify the GROUP BY part explicitly in plain SQL.
     * {@see ExpressionInterface} object can be passed as well.
     *
     * @return $this the query object itself
     *
     * {@see groupBy()}
     */
    public function addGroupBy(array|string|ExpressionInterface $columns): self;

    /**
     * Adds additional ORDER BY columns to the query.
     *
     * @param array|ExpressionInterface|string $columns the columns (and the directions) to be ordered by.
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
    public function addOrderBy(array|string|ExpressionInterface $columns): self;

    /**
     * Add more columns to the SELECT part of the query.
     *
     * Note, that if {@see select} has not been specified before, you should include `*` explicitly if you want to
     * select all remaining columns too:
     *
     * ```php
     * $query->addSelect(["*", "CONCAT(first_name, ' ', last_name) AS full_name"])->one();
     * ```
     *
     * @param array|ExpressionInterface|string $columns the columns to add to the select. See {@see select()} for more
     * details about the format of this parameter.
     *
     * @return static the query object itself.
     *
     * {@see select()}
     */
    public function addSelect(array|string|ExpressionInterface $columns): self;

    /**
     * Adds a filtering condition for a specific column and allow the user to choose a filter operator.
     *
     * It adds WHERE condition for the given field and determines the comparison operator based on the
     * first few characters of the given value.
     *
     * The condition is added in the same way as in {@see andFilterWhere} so {@see isEmpty()|empty values} are ignored.
     * The new condition and the existing one will be joined using the `AND` operator.
     *
     * The comparison operator is intelligently determined based on the first few characters in the given value.
     * In particular, it recognizes the following operators if they appear as the leading characters in the given value:
     *
     * - `<`: the column must be less than the given value.
     * - `>`: the column must be greater than the given value.
     * - `<=`: the column must be less than or equal to the given value.
     * - `>=`: the column must be greater than or equal to the given value.
     * - `<>`: the column must not be the same as the given value.
     * - `=`: the column must be equal to the given value.
     * - If none of the above operators is detected, the `$defaultOperator` will be used.
     *
     * @param string $name the column name.
     * @param string|null $value the column value optionally prepended with the comparison operator.
     * @param string $defaultOperator The operator to use, when no operator is given in `$value`.
     * Defaults to `=`, performing an exact match.
     *
     * @throws NotSupportedException
     *
     * @return static the query object itself.
     */
    public function andFilterCompare(string $name, ?string $value, string $defaultOperator = '='): self;

    /**
     * Adds HAVING condition to the existing one but ignores {@see isEmpty()|empty operands}.
     *
     * The new condition and the existing one will be joined using the `AND` operator.
     *
     * This method is similar to {@see andHaving()}. The main difference is that this method will remove
     * {@see isEmpty()|empty query operands}. As a result, this method is best suited for building query conditions
     * based on filter values entered by users.
     *
     * @param array $condition the new HAVING condition. Please refer to {@see having()} on how to specify this
     * parameter.
     *
     * @throws NotSupportedException
     *
     * @return $this the query object itself.
     *
     * {@see filterHaving()}
     * {@see orFilterHaving()}
     */
    public function andFilterHaving(array $condition): self;

    /**
     * Adds HAVING condition to the existing one.
     * The new condition and the existing one will be joined using the `AND` operator.
     *
     * @param array|ExpressionInterface|string $condition the new HAVING condition. Please refer to {@see where()}
     * on how to specify this parameter.
     * @param array $params the parameters (name => value) to be bound to the query.
     *
     * @return $this the query object itself.
     *
     * {@see having()}
     * {@see orHaving()}
     */
    public function andHaving(array|string|ExpressionInterface $condition, array $params = []): self;

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
     * @throws NotSupportedException
     *
     * @return $this the query object itself
     *
     * {@see filterWhere()}
     * {@see orFilterWhere()}
     */
    public function andFilterWhere(array $condition): self;

    /**
     * Adds WHERE condition to the existing one.
     *
     * The new condition and the existing one will be joined using the `AND` operator.
     *
     * @param array|ExpressionInterface|string $condition the new WHERE condition. Please refer to {@see where()} on how
     * to specify this parameter.
     * @param array $params the parameters (name => value) to be bound to the query.
     *
     * @return static the query object itself.
     *
     * {@see where()}
     * {@see orWhere()}
     */
    public function andWhere(array|ExpressionInterface|string $condition, array $params = []): self;

    /**
     * Sets the value indicating whether to SELECT DISTINCT or not.
     *
     * @param bool $value whether to SELECT DISTINCT or not.
     *
     * @return static the query object itself
     */
    public function distinct(?bool $value = true): self;

    /**
     * Sets the HAVING part of the query but ignores {@see isEmpty()|empty operands}.
     *
     * This method is similar to {@see having()}. The main difference is that this method will remove
     * {@see isEmpty()|empty query operands}. As a result, this method is best suited for building query conditions
     * based on filter values entered by users.
     *
     * The following code shows the difference between this method and {@see having()}:
     *
     * ```php
     * // HAVING `age`=:age
     * $query->filterHaving(['name' => null, 'age' => 20]);
     * // HAVING `age`=:age
     * $query->having(['age' => 20]);
     * // HAVING `name` IS NULL AND `age`=:age
     * $query->having(['name' => null, 'age' => 20]);
     * ```
     *
     * Note that unlike {@see having()}, you cannot pass binding parameters to this method.
     *
     * @param array $condition the conditions that should be put in the HAVING part.
     * See {@see having()} on how to specify this parameter.
     *
     * @throws NotSupportedException
     *
     * @return $this the query object itself.
     *
     * {@see having()}
     * {@see andFilterHaving()}
     * {@see orFilterHaving()}
     */
    public function filterHaving(array $condition): self;

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
     * @throws NotSupportedException
     *
     * @return $this the query object itself
     *
     * {@see where()}
     * {@see andFilterWhere()}
     * {@see orFilterWhere()}
     */
    public function filterWhere(array $condition): self;

    /**
     * Sets the FROM part of the query.
     *
     * @param array|ExpressionInterface|string $tables the table(s) to be selected from. This can be either a string
     * (e.g. `'user'`) or an array (e.g. `['user', 'profile']`) specifying one or several table names.
     *
     * Table names can contain schema prefixes (e.g. `'public.user'`) and/or table aliases (e.g. `'user u'`).
     *
     * The method will automatically quote the table names unless it contains some parenthesis (which means the table is
     * given as a sub-query or DB expression).
     *
     * When the tables are specified as an array, you may also use the array keys as the table aliases (if a table does
     * not need alias, do not use a string key).
     *
     * Use a Query object to represent a sub-query. In this case, the corresponding array key will be used as the alias
     * for the sub-query.
     *
     * To specify the `FROM` part in plain SQL, you may pass an instance of {@see ExpressionInterface}.
     *
     * Here are some examples:
     *
     * ```php
     * // SELECT * FROM  `user` `u`, `profile`;
     * $query = (new \Yiisoft\Db\Query\Query)->from(['u' => 'user', 'profile']);
     *
     * // SELECT * FROM (SELECT * FROM `user` WHERE `active` = 1) `activeusers`;
     * $subquery = (new \Yiisoft\Db\Query\Query)->from('user')->where(['active' => true])
     * $query = (new \Yiisoft\Db\Query\Query)->from(['activeusers' => $subquery]);
     *
     * // subquery can also be a string with plain SQL wrapped in parentheses
     * // SELECT * FROM (SELECT * FROM `user` WHERE `active` = 1) `activeusers`;
     * $subquery = "(SELECT * FROM `user` WHERE `active` = 1)";
     * $query = (new \Yiisoft\Db\Query\Query)->from(['activeusers' => $subquery]);
     * ```
     *
     * @return static the query object itself
     */
    public function from(array|ExpressionInterface|string $tables): self;

    /**
     * Sets the GROUP BY part of the query.
     *
     * @param array|ExpressionInterface|string $columns the columns to be grouped by.
     * Columns can be specified in either a string (e.g. "id, name") or an array (e.g. ['id', 'name']).
     * The method will automatically quote the column names unless a column contains some parenthesis (which means the
     * column contains a DB expression).
     *
     * Note that if your group-by is an expression containing commas, you should always use an array to represent the
     * group-by information. Otherwise, the method will not be able to correctly determine the group-by columns.
     *
     * {@see ExpressionInterface} object can be passed to specify the GROUP BY part explicitly in plain SQL.
     * {@see ExpressionInterface} object can be passed as well.
     *
     * @return $this the query object itself.
     *
     * {@see addGroupBy()}
     */
    public function groupBy(array|string|ExpressionInterface $columns): self;

    /**
     * Sets the HAVING part of the query.
     *
     * @param array|ExpressionInterface|string|null $condition the conditions to be put after HAVING.
     * Please refer to {@see where()} on how to specify this parameter.
     * @param array $params the parameters (name => value) to be bound to the query.
     *
     * @return $this the query object itself.
     *
     * {@see andHaving()}
     * {@see orHaving()}
     */
    public function having(array|ExpressionInterface|string|null $condition, array $params = []): self;

    /**
     * Sets the {@see indexBy} property.
     *
     * @param Closure|string|null $column the name of the column by which the query results should be indexed by.
     * This can also be a callable (e.g. anonymous function) that returns the index value based on the given row data.
     * The signature of the callable should be:
     *
     * ```php
     * function ($row)
     * {
     *     // return the index value corresponding to $row
     * }
     * ```
     *
     * @return QueryInterface the query object itself.
     */
    public function indexBy(string|Closure|null $column): self;

    /**
     * Appends an INNER JOIN part to the query.
     *
     * @param array|string $table the table to be joined.
     * Use a string to represent the name of the table to be joined.
     * The table name can contain a schema prefix (e.g. 'public.user') and/or table alias (e.g. 'user u').
     * The method will automatically quote the table name unless it contains some parenthesis (which means the table is
     * given as a sub-query or DB expression).
     * Use an array to represent joining with a sub-query. The array must contain only one element.
     * The value must be a {@see Query} object representing the sub-query while the corresponding key represents the
     * alias for the sub-query.
     * @param array|string $on the join condition that should appear in the ON part.
     * Please refer to {@see join()} on how to specify this parameter.
     * @param array $params the parameters (name => value) to be bound to the query.
     *
     * @return static the query object itself.
     */
    public function innerJoin(array|string $table, array|string $on = '', array $params = []): self;

    /**
     * Appends a JOIN part to the query.
     *
     * The first parameter specifies what type of join it is.
     *
     * @param string $type  the type of join, such as INNER JOIN, LEFT JOIN.
     * @param array|string $table the table to be joined.
     * Use a string to represent the name of the table to be joined.
     * The table name can contain a schema prefix (e.g. 'public.user') and/or table alias (e.g. 'user u').
     * The method will automatically quote the table name unless it contains some parenthesis (which means the table is
     * given as a sub-query or DB expression).
     * Use an array to represent joining with a sub-query. The array must contain only one element.
     * The value must be a {@see Query} object representing the sub-query while the corresponding key represents the
     * alias for the sub-query.
     * @param array|string $on the join condition that should appear in the ON part.
     * Please refer to {@see where()} on how to specify this parameter.
     *
     * Note that the array format of {@see where()} is designed to match columns to values instead of columns to
     * columns, so the following would **not** work as expected: `['post.author_id' => 'user.id']`, it would match the
     * `post.author_id` column value against the string `'user.id'`.
     *
     * It is recommended to use the string syntax here which is more suited for a join:
     *
     * ```php
     * 'post.author_id = user.id'
     * ```
     * @param array $params the parameters (name => value) to be bound to the query.
     *
     * @return static the query object itself.
     */
    public function join(string $type, array|string $table, array|string $on = '', array $params = []): self;

    /**
     * Appends a LEFT OUTER JOIN part to the query.
     *
     * @param array|string $table the table to be joined.
     * Use a string to represent the name of the table to be joined.
     * The table name can contain a schema prefix (e.g. 'public.user') and/or table alias (e.g. 'user u').
     * The method will automatically quote the table name unless it contains some parenthesis (which means the table is
     * given as a sub-query or DB expression).
     * Use an array to represent joining with a sub-query. The array must contain only one element.
     * The value must be a {@see Query} object representing the sub-query while the corresponding key represents the
     * alias for the sub-query.
     * @param array|string $on the join condition that should appear in the ON part.
     * Please refer to {@see join()} on how to specify this parameter.
     * @param array $params the parameters (name => value) to be bound to the query.
     *
     * @return static the query object itself.
     */
    public function leftJoin(array|string $table, array|string $on = '', array $params = []): self;

    /**
     * Sets the LIMIT part of the query.
     *
     * @param Expression|int|null $limit the limit. Use null or negative value to disable limit.
     *
     * @return $this the query object itself
     */
    public function limit(Expression|int|null $limit): self;

    /**
     * Sets the OFFSET part of the query.
     *
     * @param Expression|int|null $offset $offset the offset. Use null or negative value to disable offset.
     *
     * @return QueryInterface the query object itself
     */
    public function offset(Expression|int|null $offset): self;

    /**
     * Sets the ORDER BY part of the query.
     *
     * @param array|ExpressionInterface|string $columns the columns (and the directions) to be ordered by.
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
    public function orderBy(array|string|ExpressionInterface $columns): self;

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
     * @throws NotSupportedException
     *
     * @return $this the query object itself
     *
     * {@see filterWhere()}
     * {@see andFilterWhere()}
     */
    public function orFilterWhere(array $condition): self;

    /**
     * Adds HAVING condition to the existing one but ignores {@see isEmpty()|empty operands}.
     *
     * The new condition and the existing one will be joined using the `OR` operator.
     *
     * This method is similar to {@see orHaving()}. The main difference is that this method will remove
     * {@see isEmpty()|empty query operands}. As a result, this method is best suited for building query conditions
     * based on filter values entered by users.
     *
     * @param array $condition the new HAVING condition. Please refer to {@see having()} on how to specify this
     * parameter.
     *
     * @throws NotSupportedException
     *
     * @return $this the query object itself.
     *
     * {@see filterHaving()}
     * {@see andFilterHaving()}
     */
    public function orFilterHaving(array $condition): self;

    /**
     * Adds HAVING condition to the existing one.
     *
     * The new condition and the existing one will be joined using the `OR` operator.
     *
     * @param array|ExpressionInterface|string $condition the new HAVING condition. Please refer to {@see where()}
     * on how to specify this parameter.
     * @param array $params the parameters (name => value) to be bound to the query.
     *
     * @return $this the query object itself.
     *
     * {@see having()}
     * {@see andHaving()}
     */
    public function orHaving(array|string|ExpressionInterface $condition, array $params = []): self;

    /**
     * Adds WHERE condition to the existing one.
     *
     * The new condition and the existing one will be joined using the `OR` operator.
     *
     * @param array|ExpressionInterface|string $condition the new WHERE condition. Please refer to {@see where()} on how
     * to specify this parameter.
     * @param array $params the parameters (name => value) to be bound to the query.
     *
     * @return static the query object itself.
     *
     * {@see where()}
     * {@see andWhere()}
     */
    public function orWhere(array|string|ExpressionInterface $condition, array $params = []): self;

    /**
     * Appends a RIGHT OUTER JOIN part to the query.
     *
     * @param array|string $table the table to be joined.
     * Use a string to represent the name of the table to be joined.
     * The table name can contain a schema prefix (e.g. 'public.user') and/or table alias (e.g. 'user u').
     * The method will automatically quote the table name unless it contains some parenthesis (which means the table is
     * given as a sub-query or DB expression).
     * Use an array to represent joining with a sub-query. The array must contain only one element.
     * The value must be a {@see Query} object representing the sub-query while the corresponding key represents the
     * alias for the sub-query.
     * @param array|string $on the join condition that should appear in the ON part.
     * Please refer to {@see join()} on how to specify this parameter.
     * @param array $params the parameters (name => value) to be bound to the query.
     *
     * @return static the query object itself.
     */
    public function rightJoin(array|string $table, array|string $on = '', array $params = []): self;

    /**
     * Sets the SELECT part of the query.
     *
     * @param array|ExpressionInterface|string $columns the columns to be selected.
     * Columns can be specified in either a string (e.g. "id, name") or an array (e.g. ['id', 'name']).
     * Columns can be prefixed with table names (e.g. "user.id") and/or contain column aliases
     * (e.g. "user.id AS user_id").
     *
     * The method will automatically quote the column names unless a column contains some parenthesis (which means the
     * column contains a DB expression). A DB expression may also be passed in form of an {@see ExpressionInterface}
     * object.
     *
     * Note that if you are selecting an expression like `CONCAT(first_name, ' ', last_name)`, you should use an array
     * to specify the columns. Otherwise, the expression may be incorrectly split into several parts.
     *
     * When the columns are specified as an array, you may also use array keys as the column aliases (if a column does
     * not need alias, do not use a string key).
     * @param string|null $option additional option that should be appended to the 'SELECT' keyword. For example,
     * in MySQL, the option 'SQL_CALC_FOUND_ROWS' can be used.
     *
     * @return static the query object itself.
     */
    public function select(array|string|ExpressionInterface $columns, ?string $option = null): self;

    public function selectOption(?string $value): QueryInterface;

    public function setJoin(array $value): QueryInterface;

    public function setUnion(array $value): QueryInterface;

    /**
     * Appends a SQL statement using UNION operator.
     *
     * @param QueryInterface|string $sql $sql the SQL statement to be appended using UNION.
     * @param bool $all `TRUE` if using UNION ALL and `FALSE` if using UNION.
     *
     * @return static the query object itself.
     */
    public function union(QueryInterface|string $sql, bool $all = false): self;

    /**
     * Sets the WHERE part of the query.
     *
     * The `$condition` specified as an array can be in one of the following two formats:
     *
     * - hash format: `['column1' => value1, 'column2' => value2, ...]`
     * - operator format: `[operator, operand1, operand2, ...]`
     *
     * A condition in hash format represents the following SQL expression in general:
     * `column1=value1 AND column2=value2 AND ...`. In case when a value is an array,
     * an `IN` expression will be generated. And if a value is `null`, `IS NULL` will be used in the generated
     * expression. Below are some examples:
     *
     * - `['type' => 1, 'status' => 2]` generates `(type = 1) AND (status = 2)`.
     * - `['id' => [1, 2, 3], 'status' => 2]` generates `(id IN (1, 2, 3)) AND (status = 2)`.
     * - `['status' => null]` generates `status IS NULL`.
     *
     * A condition in operator format generates the SQL expression according to the specified operator, which can be one
     * of the following:
     *
     * - **and**: the operands should be concatenated together using `AND`. For example,
     *   `['and', 'id=1', 'id=2']` will generate `id=1 AND id=2`. If an operand is an array,
     *   it will be converted into a string using the rules described here. For example,
     *   `['and', 'type=1', ['or', 'id=1', 'id=2']]` will generate `type=1 AND (id=1 OR id=2)`.
     *   The method will *not* do any quoting or escaping.
     *
     * - **or**: similar to the `and` operator except that the operands are concatenated using `OR`. For example,
     *   `['or', ['type' => [7, 8, 9]], ['id' => [1, 2, 3]]]` will generate `(type IN (7, 8, 9) OR (id IN (1, 2, 3)))`.
     *
     * - **not**: this will take only one operand and build the negation of it by prefixing the query string with `NOT`.
     *   For example `['not', ['attribute' => null]]` will result in the condition `NOT (attribute IS NULL)`.
     *
     * - **between**: operand 1 should be the column name, and operand 2 and 3 should be the
     *   starting and ending values of the range that the column is in.
     *   For example, `['between', 'id', 1, 10]` will generate `id BETWEEN 1 AND 10`.
     *
     * - **not between**: similar to `between` except the `BETWEEN` is replaced with `NOT BETWEEN`
     *   in the generated condition.
     *
     * - **in**: operand 1 should be a column or DB expression, and operand 2 be an array representing
     *   the range of the values that the column or DB expression should be in. For example,
     *   `['in', 'id', [1, 2, 3]]` will generate `id IN (1, 2, 3)`.
     *   The method will properly quote the column name and escape values in the range.
     *
     *   To create a composite `IN` condition you can use and array for the column name and value, where the values are
     *   indexed by the column name:
     *   `['in', ['id', 'name'], [['id' => 1, 'name' => 'foo'], ['id' => 2, 'name' => 'bar']] ]`.
     *
     *   You may also specify a sub-query that is used to get the values for the `IN`-condition:
     *   `['in', 'user_id', (new Query())->select('id')->from('users')->where(['active' => 1])]`
     *
     * - **not in**: similar to the `in` operator except that `IN` is replaced with `NOT IN` in the generated condition.
     *
     * - **like**: operand 1 should be a column or DB expression, and operand 2 be a string or an array representing
     *   the values that the column or DB expression should be like.
     *   For example, `['like', 'name', 'tester']` will generate `name LIKE '%tester%'`.
     *   When the value range is given as an array, multiple `LIKE` predicates will be generated and concatenated
     *   using `AND`. For example, `['like', 'name', ['test', 'sample']]` will generate
     *   `name LIKE '%test%' AND name LIKE '%sample%'`.
     *   The method will properly quote the column name and escape special characters in the values.
     *   Sometimes, you may want to add the percentage characters to the matching value by yourself, you may supply
     *   a third operand `false` to do so. For example, `['like', 'name', '%tester', false]` will generate
     *   `name LIKE '%tester'`.
     *
     * - **or like**: similar to the `like` operator except that `OR` is used to concatenate the `LIKE` predicates when
     *   operand 2 is an array.
     *
     * - **not like**: similar to the `like` operator except that `LIKE` is replaced with `NOT LIKE` in the generated
     *   condition.
     *
     * - **or not like**: similar to the `not like` operator except that `OR` is used to concatenate the `NOT LIKE`
     *   predicates.
     *
     * - **exists**: operand 1 is a query object that used to build an `EXISTS` condition. For example
     *   `['exists', (new Query())->select('id')->from('users')->where(['active' => 1])]` will result in the following
     *   SQL expression:
     *   `EXISTS (SELECT "id" FROM "users" WHERE "active"=1)`.
     *
     * - **not exists**: similar to the `exists` operator except that `EXISTS` is replaced with `NOT EXISTS` in the
     *   generated condition.
     *
     * - Additionally you can specify arbitrary operators as follows: A condition of `['>=', 'id', 10]` will result
     *   in the following SQL expression: `id >= 10`.
     *
     * **Note that this method will override any existing WHERE condition. You might want to use {@see andWhere()}
     * or {@see orWhere()} instead.**
     *
     * @param array|ExpressionInterface|string|null $condition the conditions that should be put in the WHERE part.
     * @param array $params the parameters (name => value) to be bound to the query.
     *
     * @return QueryInterface the query object itself.
     *
     * {@see andWhere()}
     * {@see orWhere()}
     */
    public function where(array|string|ExpressionInterface|null $condition, array $params = []): self;

    /**
     * Prepends a SQL statement using WITH syntax.
     *
     * @param QueryInterface|string $query the SQL statement to be appended using UNION.
     * @param string $alias query alias in WITH construction.
     * @param bool $recursive TRUE if using WITH RECURSIVE and FALSE if using WITH.
     *
     * @return static the query object itself.
     */
    public function withQuery(QueryInterface|string $query, string $alias, bool $recursive = false): self;

    public function withQueries(array $value): QueryInterface;
}
