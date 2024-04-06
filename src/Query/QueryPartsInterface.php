<?php

declare(strict_types=1);

namespace Yiisoft\Db\Query;

use Closure;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\ExpressionInterface;

/**
 * This interface defines a set of methods to create and manipulate the different parts of a database
 * query, such as the {@see addGroupBy()}, {@see addSelect()}, {@see addOrderBy()}, {@see andFilterCompare()}, etc.
 *
 * {@see Query} uses these methods to build and manipulate SQL statements.
 *
 * @psalm-import-type ParamsType from ConnectionInterface
 */
interface QueryPartsInterface
{
    /**
     * Adds more group-by columns to the existing ones.
     *
     * @param array|ExpressionInterface|string $columns More columns to be grouped by.
     * Columns can be specified in either a string (for example 'id, name') or an array (such as ['id', 'name']).
     * The method will automatically quote the column names unless a column has some parenthesis (which means the
     * column has a DB expression).
     * Note that if your group-by is an expression containing commas, you should always use an array to represent the
     * group-by information.
     * Otherwise, the method won't be able to correctly decide the group-by columns.
     *
     * {@see ExpressionInterface} object can be passed to specify the `GROUP` BY part explicitly in plain SQL.
     * {@see ExpressionInterface} object can be passed as well.
     *
     * @see groupBy()
     */
    public function addGroupBy(array|string|ExpressionInterface $columns): static;

    /**
     * Adds more `ORDER BY` columns to the query.
     *
     * @param array|ExpressionInterface|string $columns The columns (and the directions) to be ordered by.
     * Columns can be specified in either a string (for example "id ASC, name DESC") or an array
     * (for example, `['id' => SORT_ASC, 'name' => SORT_DESC]`).
     * The method will automatically quote the column names unless a column has some parenthesis (which means the column
     * has a DB expression).
     * Note that if your order-by is an expression containing commas, you should always use an array to represent the
     * order-by information.
     * Otherwise, the method won't be able to correctly decide the order-by columns.
     * Since {@see ExpressionInterface} an object can be passed to specify the ORDER BY part explicitly in plain SQL.
     *
     * @see orderBy()
     */
    public function addOrderBy(array|string|ExpressionInterface $columns): static;

    /**
     * Add more columns to the SELECT part of the query.
     *
     * Note, that if {@see select} hasn't been specified before, you should include `*` explicitly if you want to select
     * all remaining columns too:
     *
     * ```php
     * $query->addSelect(["*", "CONCAT(first_name, ' ', last_name) AS full_name"])->one();
     * ```
     *
     * @param array|ExpressionInterface|string $columns The columns to add to the select.
     *
     * {@see select()} for more details about the format of this parameter.
     */
    public function addSelect(array|string|ExpressionInterface $columns): static;

    /**
     * Adds a filtering condition for a specific column and allow the user to choose a filter operator.
     *
     * It adds `WHERE` condition for the given field and determines the comparison operator based on the first few
     * characters of the given value.
     *
     * The condition is added in the same way as in {@see andFilterWhere()} so {@see Query::isEmpty()} are ignored.
     *
     * The new condition and the existing one are joined using the `AND` operator.
     *
     * The comparison operator is intelligently determined based on the first few characters in the given value.
     *
     * In particular, it recognizes the following operators if they appear as the leading characters in the given value:
     * - `<`: the column must be less than the given value.
     * - `>`: the column must be greater than the given value.
     * - `<=`: the column must be less than or equal to the given value.
     * - `>=`: the column must be greater than or equal to the given value.
     * - `<>`: the column must not be the same as the given value.
     * - `=`: the column must be equal to the given value.
     * - If operator isn't regognized, the `$defaultOperator` is used.
     *
     * @param string $column The column name.
     * @param string|null $value The column value optionally prepended with the comparison operator.
     * @param string $defaultOperator The operator to use when no operator is given in `$value`.
     * Defaults to `=`, performing an exact match.
     *
     * @throws NotSupportedException If this query doesn't support filtering.
     */
    public function andFilterCompare(string $column, string|null $value, string $defaultOperator = '='): static;

    /**
     * Adds HAVING condition to the existing one but ignores {@see Query::isEmpty()}.
     *
     * The new condition and the existing one will be joined using the `AND` operator.
     *
     * This method is similar to {@see andHaving()}. The main difference is that this method will remove
     * {@see Query::isEmpty()}.
     *
     * As a result, this method is best suited for building query conditions based on filter values entered by users.
     *
     * @param array $condition The new `HAVING` condition.
     * Please refer to {@see having()} on how to specify this parameter.
     *
     * @throws NotSupportedException If this query doesn't support filtering.
     *
     * @see filterHaving()
     * @see orFilterHaving()
     */
    public function andFilterHaving(array $condition): static;

    /**
     * Adds HAVING condition to the existing one.
     *
     * The new condition and the existing one will be joined using the `AND` operator.
     *
     * @param array|ExpressionInterface|string $condition The new HAVING condition.
     * Please refer to {@see where()} on how to specify this parameter.
     * @param array $params The parameters (name => value) to be bound to the query.
     *
     * @psalm-param ParamsType $params
     *
     * @see having()
     * @see orHaving()
     */
    public function andHaving(array|string|ExpressionInterface $condition, array $params = []): static;

    /**
     * Adds `WHERE` condition to the existing one but ignores {@see Query::isEmpty()}.
     *
     * The new condition and the existing one will be joined using the `AND` operator.
     *
     * This method is similar to {@see andWhere()}. The main difference is that this method will remove
     * {@see Query::isEmpty()}.
     *
     * As a result, this method is best suited for building query conditions based on filter values entered by users.
     *
     * @param array $condition The new `WHERE` condition.
     * Please refer to {@see where()} on how to specify this parameter.
     *
     * @throws NotSupportedException If this query doesn't support filtering.
     *
     * @see filterWhere()
     * @see orFilterWhere()
     */
    public function andFilterWhere(array $condition): static;

    /**
     * Adds `WHERE` condition to the existing one.
     *
     * The new condition and the existing one will be joined using the `AND` operator.
     *
     * @param array|ExpressionInterface|string $condition The new `WHERE` condition.
     * Please refer to {@see where()} on how to specify this parameter.
     * @param array $params The parameters (name => value) to be bound to the query.
     *
     * @psalm-param ParamsType $params
     *
     * @see where()
     * @see orWhere()
     */
    public function andWhere(array|ExpressionInterface|string $condition, array $params = []): static;

    /**
     * Sets the value indicating whether to `SELECT DISTINCT` or not.
     *
     * @param bool $value Whether to `SELECT DISTINCT` or not.
     */
    public function distinct(bool|null $value = true): static;

    /**
     * Sets the `HAVING` part of the query but ignores {@see Query::isEmpty()}.
     *
     * This method is similar to {@see having()}. The main difference is that this method will remove
     * {@see Query::isEmpty()}. As a result, this method is best suited for building query conditions based on filter
     * values entered by users.
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
     * Note that unlike {@see having()}, you can't pass binding parameters to this method.
     *
     * @param array $condition The conditions that should be in the `HAVING` part.
     * See {@see having()} on how to specify this parameter.
     *
     * @throws NotSupportedException If this query doesn't support filtering.
     *
     * @see having()
     * @see andFilterHaving()
     * @see orFilterHaving()
     */
    public function filterHaving(array $condition): static;

    /**
     * Sets the `WHERE` part of the query but ignores {@see Query::isEmpty()}.
     *
     * This method is similar to {@see where()}.
     *
     * The main difference is that this method will remove {@see Query::isEmpty()}.
     *
     * As a result, this method is best suited for building query conditions based on filter values entered by users.
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
     * Note that unlike {@see where()}, you can't pass binding parameters to this method.
     *
     * @param array $condition The conditions that should be in the `WHERE` part.
     * {@see where()} On how to specify this parameter.
     *
     * @throws NotSupportedException If this query doesn't support filtering.
     *
     * @see where()
     * @see andFilterWhere()
     * @see orFilterWhere()
     */
    public function filterWhere(array $condition): static;

    /**
     * Sets the `FROM` part of the query.
     *
     * @param array|ExpressionInterface|string $tables The table(s) to select from.
     * This can be either a string (for example, `'user'`) or an array (such as `['user', 'profile']`) specifying one or
     * several table names.
     * Table names can contain schema prefixes (such as `'public.user'`) and/or table aliases (such as `'user u'`).
     * The method will automatically quote the table names unless it has some parenthesis (which means the table is
     * given as a sub-query or DB expression).
     * When the tables are specified as an array, you may also use the array keys as the table aliases (if a table
     * doesn't need alias, don't use a string key).
     * Use a Query object to represent a sub-query. In this case, the corresponding array key will be used as the alias
     * for the sub-query.
     * To specify the `FROM` part in plain SQL, you may pass an instance of {@see ExpressionInterface}.
     * Here are some examples:
     *
     * ```php
     * // SELECT * FROM  `user` `u`, `profile`;
     * $query = (new \Yiisoft\Db\Query\Query)->from(['u' => 'user', 'profile']);
     *
     * // SELECT * FROM (SELECT * FROM `user` WHERE `active` = 1) `activeusers`;
     * $subQuery = (new \Yiisoft\Db\Query\Query)->from('user')->where(['active' => true])
     * $query = (new \Yiisoft\Db\Query\Query)->from(['activeusers' => $subQuery]);
     *
     * // subQuery can also be a string with plain SQL wrapped in parentheses
     * // SELECT * FROM (SELECT * FROM `user` WHERE `active` = 1) `activeusers`;
     * $subQuery = "(SELECT * FROM `user` WHERE `active` = 1)";
     * $query = (new \Yiisoft\Db\Query\Query)->from(['activeusers' => $subQuery]);
     * ```
     */
    public function from(array|ExpressionInterface|string $tables): static;

    /**
     * Sets the `GROUP BY` part of the query.
     *
     * @param array|ExpressionInterface|string $columns The columns to be grouped by.
     * Columns can be specified in either a string (for example "id, name") or an array (such as ['id', 'name']).
     * The method will automatically quote the column names unless a column has some parenthesis (which means the
     * column has a DB expression).
     * Note that if your group-by is an expression containing commas, you should always use an array to represent the
     * group-by information.
     * Otherwise, the method won't be able to correctly decide the group-by columns.
     *
     * {@see ExpressionInterface} object can be passed to specify the `GROUP BY` part explicitly in plain SQL.
     * {@see ExpressionInterface} object can be passed as well.
     *
     * @see addGroupBy()
     */
    public function groupBy(array|string|ExpressionInterface $columns): static;

    /**
     * Sets the `HAVING` part of the query.
     *
     * @param array|ExpressionInterface|string|null $condition The conditions to be put after `HAVING`.
     * Please refer to {@see where()} on how to specify this parameter.
     * @param array $params The parameters (name => value) to bind to the query.
     *
     * @psalm-param ParamsType $params
     *
     * @see andHaving()
     * @see orHaving()
     */
    public function having(array|ExpressionInterface|string|null $condition, array $params = []): static;

    /**
     * Sets the {@see indexBy} property.
     *
     * @param Closure|string|null $column The name of the column by which the query results should be indexed by.
     * This can also be callable (for example, anonymous function) that returns the index value based on the given row
     * data.
     * The signature of the callable should be:
     *
     * ```php
     * function ($row)
     * {
     *     // return the index value corresponding to $row
     * }
     * ```
     */
    public function indexBy(string|Closure|null $column): static;

    /**
     * Appends an `INNER JOIN` part to the query.
     *
     * @param array|string $table The table to be joined.
     * Use a string to represent the name of the table to be joined.
     * The table name can contain a schema prefix (such as 'public.user') and/or table alias (such as 'user u').
     * The method will automatically quote the table name unless it has some parenthesis (which means the table is
     * given as a sub-query or DB expression).
     * Use an array to represent joining with a sub-query. The array must contain only one element.
     * The value must be a {@see Query} object representing the sub-query while the corresponding key represents the
     * alias for the sub-query.
     * @param array|string $on The join condition that should appear in the ON part. Please refer to {@see join()} on
     * how to specify this parameter.
     * @param array $params The parameters (name => value) to bind to the query.
     *
     * @psalm-param ParamsType $params
     */
    public function innerJoin(array|string $table, array|string $on = '', array $params = []): static;

    /**
     * Appends a JOIN part to the query.
     *
     * The first parameter specifies what type of join it is.
     *
     * @param string $type The type of join, such as `INNER JOIN`, `LEFT JOIN`.
     * @param array|string $table The table to join.
     * Use a string to represent the name of the table to join.
     * The table name can contain a schema prefix (such as 'public.user') and/or table alias (e.g. 'user u').
     * The method will automatically quote the table name unless it has some parenthesis (which means the table is
     * given as a sub-query or DB expression).
     * Use an array to represent joining with a sub-query. The array must contain only one element.
     * The value must be a {@see Query} object representing the sub-query while the corresponding key represents the
     * alias for the sub-query.
     * @param array|string $on The join condition that should appear in the ON part. Please refer to {@see where()} on
     * how to specify this parameter.
     * Note that the array format of {@see where()} is designed to match columns to values instead of columns to
     * columns, so the following would **not** work as expected: `['post.author_id' => 'user.id']`, it would match the
     * `post.author_id` column value against the string `'user.id'`.
     * It's recommended to use the string syntax here which is more suited for a join:
     *
     * ```php
     * 'post.author_id = user.id'
     * ```
     * @param array $params The parameters (name => value) to bind to the query.
     *
     * @psalm-param ParamsType $params
     */
    public function join(string $type, array|string $table, array|string $on = '', array $params = []): static;

    /**
     * Appends a `LEFT OUTER JOIN` part to the query.
     *
     * @param array|string $table The table to join.
     * Use a string to represent the name of the table to join.
     * The table name can contain a schema prefix (such as 'public.user') and/or table alias (such as 'user u').
     * The method will automatically quote the table name unless it has some parenthesis (which means the table is
     * given as a sub-query or DB expression).
     * Use an array to represent joining with a sub-query. The array must contain only one element.
     * The value must be a {@see Query} object representing the sub-query while the corresponding key represents the
     * alias for the sub-query.
     * @param array|string $on The join condition that should appear in the ON part. Please refer to {@see join()} on
     * how to specify this parameter.
     * @param array $params The parameters (name => value) to bind to the query.
     *
     * @psalm-param ParamsType $params
     */
    public function leftJoin(array|string $table, array|string $on = '', array $params = []): static;

    /**
     * Sets the `LIMIT` part of the query.
     *
     * @param ExpressionInterface|int|null $limit The limit. Use null or negative value to disable limit.
     */
    public function limit(ExpressionInterface|int|null $limit): static;

    /**
     * Sets the `OFFSET` part of the query.
     *
     * @param ExpressionInterface|int|null $offset $offset The offset. Use `null` or negative value to disable offset.
     */
    public function offset(ExpressionInterface|int|null $offset): static;

    /**
     * Sets the `ORDER BY` part of the query.
     *
     * @param array|ExpressionInterface|string $columns The columns (and the directions) to be ordered by.
     * Columns can be specified in either a string (for example `"id ASC, name DESC"`) or an array
     * (such as `['id' => SORT_ASC, 'name' => SORT_DESC]`).
     * The method will automatically quote the column names unless a column has some parenthesis
     * (which means the column has a DB expression).
     * Note that if your order-by is an expression containing commas, you should always use an array to represent the
     * order-by information.
     * Otherwise, the method won't be able to correctly decide the order-by columns.
     * Since {@see ExpressionInterface} an object can be passed to specify the `ORDER BY` part explicitly in plain SQL.
     *
     * @see addOrderBy()
     */
    public function orderBy(array|string|ExpressionInterface $columns): static;

    /**
     * Adds `WHERE` condition to the existing one but ignores {@see Query::isEmpty()}.
     *
     * The new condition and the existing one will be joined using the 'OR' operator.
     *
     * This method is similar to {@see orWhere()}. The main difference is that this method will remove
     * {@see Query::isEmpty()}. As a result, this method is best suited for building query conditions based on filter
     * values entered by users.
     *
     * @param array $condition The new `WHERE` condition. Please refer to {@see where()} on how to specify this parameter.
     *
     * @throws NotSupportedException
     *
     * @see filterWhere()
     * @see andFilterWhere()
     */
    public function orFilterWhere(array $condition): static;

    /**
     * Adds HAVING condition to the existing one but ignores {@see Query::isEmpty()}.
     *
     * The new condition and the existing one will be joined using the `OR` operator.
     *
     * This method is similar to {@see orHaving()}. The main difference is that this method will remove
     * {@see Query::isEmpty()}. As a result, this method is best suited for building query conditions based on filter
     * values entered by users.
     *
     * @param array $condition The new `HAVING` condition. Please refer to {@see having()} on how to specify this
     * parameter.
     *
     * @throws NotSupportedException
     *
     * @see filterHaving()
     * @see andFilterHaving()
     */
    public function orFilterHaving(array $condition): static;

    /**
     * Adds `HAVING` condition to the existing one.
     *
     * The new condition and the existing one will be joined using the `OR` operator.
     *
     * @param array|ExpressionInterface|string $condition The new `HAVING` condition.
     * Please refer to {@see where()} on how to specify this parameter.
     * @param array $params The parameters (name => value) to bind to the query.
     *
     * @psalm-param ParamsType $params
     *
     * @see having()
     * @see andHaving()
     */
    public function orHaving(array|string|ExpressionInterface $condition, array $params = []): static;

    /**
     * Adds `WHERE` condition to the existing one.
     *
     * The new condition and the existing one will be joined using the `OR` operator.
     *
     * @param array|ExpressionInterface|string $condition The new `WHERE` condition.
     * Please refer to {@see where()} on how to specify this parameter.
     * @param array $params The parameters (name => value) to bind to the query.
     *
     * @psalm-param ParamsType $params
     *
     * @see where()
     * @see andWhere()
     */
    public function orWhere(array|string|ExpressionInterface $condition, array $params = []): static;

    /**
     * Appends a `RIGHT OUTER JOIN` part to the query.
     *
     * @param array|string $table The table to be joined.
     * Use a string to represent the name of the table to be joined.
     * The table name can contain a schema prefix (such as `public.user`) and/or table alias (such as `user u`).
     * The method will automatically quote the table name unless it has some parenthesis (which means the table is
     * given as a sub-query or DB expression).
     * Use an array to represent joining with a sub-query. The array must contain only one element.
     * The value must be a {@see Query} object representing the sub-query while the corresponding key represents the
     * alias for the sub-query.
     * @param array|string $on The join condition that should appear in the ON part.
     * Please refer to {@see join()} on how to specify this parameter.
     * @param array $params The parameters (name => value) to be bound to the query.
     *
     * @psalm-param ParamsType $params
     */
    public function rightJoin(array|string $table, array|string $on = '', array $params = []): static;

    /**
     * Sets the `SELECT` part of the query.
     *
     * @param array|ExpressionInterface|string $columns The columns to be selected.
     * Columns can be specified in either a string (for example `id, name`) or an array (such as `['id', 'name']`).
     * Columns can be prefixed with table names (such as `user.id`) and/or contain column aliases
     * (for example `user.id AS user_id`).
     * The method will automatically quote the column names unless a column has some parenthesis (which means the
     * column has a DB expression).
     * A DB expression may also be passed in form of an {@see ExpressionInterface} object.
     * Note that if you are selecting an expression like `CONCAT(first_name, ' ', last_name)`, you should use an array
     * to specify the columns. Otherwise, the expression may be incorrectly split into several parts.
     * When the columns are specified as an array, you may also use array keys as the column aliases (if a column
     * doesn't need alias, don't use a string key).
     * @param string|null $option More option that should be appended to the 'SELECT' keyword. For example, in MySQL,
     * the option 'SQL_CALC_FOUND_ROWS' can be used.
     */
    public function select(array|string|ExpressionInterface $columns, string $option = null): static;

    /**
     * It allows you to specify more options for the `SELECT` clause of an SQL statement.
     *
     * @param string|null $value More option that should be appended to the 'SELECT' keyword.
     * For example, in MySQL, the option `SQL_CALC_FOUND_ROWS` can be used.
     */
    public function selectOption(string|null $value): static;

    /**
     * Specify the joins for a `SELECT` statement in a database query.
     *
     * @param array $value The joins to perform in the query. The format is the following:
     *
     * ```
     * [
     *     ['INNER JOIN', 'table1', 'table1.id = table2.id'],
     *     ['LEFT JOIN', 'table3', 'table1.id = table3.id'],
     * ]
     * ```
     */
    public function setJoins(array $value): static;

    /**
     * Specify queries for a `SELECT` statement that are combined with `UNION`s.
     *
     * @param array $value The queries to union such as `['SELECT * FROM table1', 'SELECT * FROM table2']`.
     */
    public function setUnions(array $value): static;

    /**
     * Appends an SQL statement using `UNION` operator.
     *
     * @param QueryInterface|string $sql $sql The SQL statement to be appended using `UNION`.
     * @param bool $all `true` if using `UNION ALL` and `false` if using `UNION`.
     */
    public function union(QueryInterface|string $sql, bool $all = false): static;

    /**
     * Sets the `WHERE` part of the query.
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
     *   The method will quote the column name and escape values in the range.
     *
     *   To create a composite `IN` condition you can use and array for the column name and value, where the values are
     *   indexed by the column name:
     *   `['in', ['id', 'name'], [['id' => 1, 'name' => 'foo'], ['id' => 2, 'name' => 'bar']] ]`.
     *
     *   You may also specify a sub-query that's used to get the values for the `IN`-condition:
     *   `['in', 'user_id', (new Query())->select('id')->from('users')->where(['active' => 1])]`
     *
     * - **not in**: similar to the `in` operator except that `IN` is replaced with `NOT IN` in the generated condition.
     *
     * - **like**: operand 1 should be a column or DB expression, and operand 2 be a string or an array representing
     *   the values that the column or DB expression should be like.
     *   For example, `['like', 'name', 'tester']` will generate `name LIKE '%tester%'`.
     *   When the value range is given as an array, many `LIKE` predicates will be generated and concatenated using
     *   `AND`. For example, `['like', 'name', ['test', 'sample']]` will generate
     *   `name LIKE '%test%' AND name LIKE '%sample%'`.
     *   The method will quote the column name and escape special characters in the values.
     *   Sometimes, you may want to add the percentage characters to the matching value by yourself. You may supply
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
     * - Additionally, you can specify arbitrary operators as follows: A condition of `['>=', 'id', 10]` will result
     *   in the following SQL expression: `id >= 10`.
     *
     * **Note that this method will override any existing `WHERE` condition. You might want to use {@see andWhere()}
     * or {@see orWhere()} instead.**
     *
     * @param array|ExpressionInterface|string|null $condition The conditions to put in the `WHERE` part.
     * @param array $params The parameters (name => value) to bind to the query.
     *
     * @psalm-param ParamsType $params
     *
     * @see andWhere()
     * @see orWhere()
     */
    public function where(array|string|ExpressionInterface|null $condition, array $params = []): static;

    /**
     * Prepends an SQL statement using `WITH` syntax.
     *
     * @param QueryInterface|string $query The SQL statement to append using `UNION`.
     * @param string $alias The query alias in `WITH` construction.
     * @param bool $recursive Its `true` if using `WITH RECURSIVE` and `false` if using `WITH`.
     */
    public function withQuery(QueryInterface|string $query, string $alias, bool $recursive = false): static;

    /**
     * Specifies the `WITH` query clause for the query.
     *
     * @param array $withQueries The `WITH` queries to append to the query.
     */
    public function withQueries(array $withQueries): static;
}
