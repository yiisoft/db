<?php

declare(strict_types=1);

namespace Yiisoft\Db\Query;

use Closure;
use Iterator;
use Throwable;
use Yiisoft\Arrays\ArrayHelper;
use Yiisoft\Cache\Dependency\Dependency;
use Yiisoft\Db\Command\Command;
use Yiisoft\Db\Command\CommandInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Expression\ExpressionInterface;

use function array_merge;
use function count;
use function is_array;
use function is_int;
use function is_string;
use function key;
use function preg_match;
use function preg_split;
use function reset;
use function str_replace;
use function strcasecmp;
use function strlen;
use function substr;
use function trim;

/**
 * Query represents a SELECT SQL statement in a way that is independent of DBMS.
 *
 * Query provides a set of methods to facilitate the specification of different clauses in a SELECT statement. These
 * methods can be chained together.
 *
 * By calling {@see createCommand()}, we can get a {@see Command} instance which can be further used to perform/execute
 * the DB query against a database.
 *
 * For example,
 *
 * ```php
 * $query = new Query;
 * // compose the query
 * $query->select('id, name')
 *     ->from('user')
 *     ->limit(10);
 * // build and execute the query
 * $rows = $query->all();
 * // alternatively, you can create DB command and execute it
 * $command = $query->createCommand();
 * // $command->sql returns the actual SQL
 * $rows = $command->queryAll();
 * ```
 *
 * Query internally uses the {@see QueryBuilder} class to generate the SQL statement.
 *
 * A more detailed usage guide on how to work with Query can be found in the
 * [guide article on Query Builder](guide:db-query-builder).
 *
 * @property string[] $tablesUsedInFrom Table names indexed by aliases. This property is read-only.
 */
class Query implements QueryInterface
{
    use QueryTrait;

    protected array $select = [];
    protected ?string $selectOption = null;
    protected ?bool $distinct = null;
    protected array|null $from = null;
    protected array $groupBy = [];
    protected array $join = [];
    protected array|ExpressionInterface|string|null $having = null;
    protected array $union = [];
    protected array $withQueries = [];
    protected array $params = [];
    private ?Dependency $queryCacheDependency = null;
    private ConnectionInterface $db;
    private ?int $queryCacheDuration = null;

    public function __construct(ConnectionInterface $db)
    {
        $this->db = $db;
    }

    public function createCommand(): CommandInterface
    {
        [$sql, $params] = $this->db->getQueryBuilder()->build($this);

        $command = $this->db->createCommand($sql, $params);

        $this->setCommandCache($command);

        return $command;
    }

    public function prepare(QueryBuilder $builder): self
    {
        return $this;
    }

    /**
     * Starts a batch query.
     *
     * A batch query supports fetching data in batches, which can keep the memory usage under a limit.
     *
     * This method will return a {@see BatchQueryResult} object which implements the {@see Iterator} interface and can
     * be traversed to retrieve the data in batches.
     *
     * For example,
     *
     * ```php
     * $query = (new Query)->from('user');
     * foreach ($query->batch() as $rows) {
     *     // $rows is an array of 100 or fewer rows from user table
     * }
     * ```
     *
     * @param int $batchSize the number of records to be fetched in each batch.
     *
     * @return BatchQueryResult the batch query result. It implements the {@see Iterator} interface and can be
     * traversed to retrieve the data in batches.
     */
    public function batch(int $batchSize = 100): BatchQueryResult
    {
        return (new BatchQueryResult($this->db, $this))->batchSize($batchSize);
    }

    /**
     * Starts a batch query and retrieves data row by row.
     *
     * This method is similar to {@see batch()} except that in each iteration of the result, only one row of data is
     * returned. For example,
     *
     * ```php
     * $query = (new Query)->from('user');
     * foreach ($query->each() as $row) {
     * }
     * ```
     *
     * @param int $batchSize the number of records to be fetched in each batch.
     *
     * @return BatchQueryResult the batch query result. It implements the {@see Iterator} interface and can be
     * traversed to retrieve the data in batches.
     */
    public function each(int $batchSize = 100): BatchQueryResult
    {
        return (new BatchQueryResult($this->db, $this, true))->batchSize($batchSize);
    }

    /**
     * Executes the query and returns all results as an array.
     *
     * If this parameter is not given, the `db` application component will be used.
     *
     * @throws Exception|InvalidConfigException|Throwable
     *
     * @return array the query results. If the query results in nothing, an empty array will be returned.
     */
    public function all(): array
    {
        if ($this->emulateExecution) {
            return [];
        }

        $rows = $this->createCommand()->queryAll();

        return $this->populate($rows);
    }

    /**
     * @psalm-suppress MixedArrayOffset
     */
    public function populate(array $rows): array
    {
        if ($this->indexBy === null) {
            return $rows;
        }

        $result = [];

        /** @psalm-var array[][] */
        foreach ($rows as $row) {
            $result[ArrayHelper::getValueByPath($row, $this->indexBy)] = $row;
        }

        return $result;
    }

    /**
     * Executes the query and returns a single row of result.
     *
     * If this parameter is not given, the `db` application component will be used.
     *
     * @throws Exception|InvalidConfigException|Throwable
     *
     * @return mixed the first row (in terms of an array) of the query result. False is returned if the query
     * results in nothing.
     */
    public function one(): mixed
    {
        if ($this->emulateExecution) {
            return false;
        }

        return $this->createCommand()->queryOne();
    }

    /**
     * Returns the query result as a scalar value.
     *
     * The value returned will be the first column in the first row of the query results.
     *
     * @throws Exception|InvalidConfigException|Throwable
     *
     * @return bool|int|string|null the value of the first column in the first row of the query result. False is
     * returned if the query result is empty.
     */
    public function scalar(): bool|int|null|string
    {
        if ($this->emulateExecution) {
            return null;
        }

        return $this->createCommand()->queryScalar();
    }

    /**
     * Executes the query and returns the first column of the result.
     *
     * If this parameter is not given, the `db` application component will be used.
     *
     * @throws Exception|InvalidConfigException|Throwable
     *
     * @return array the first column of the query result. An empty array is returned if the query results in nothing.
     *
     * @psalm-suppress MixedArrayOffset
     */
    public function column(): array
    {
        if ($this->emulateExecution) {
            return [];
        }

        if ($this->indexBy === null) {
            return $this->createCommand()->queryColumn();
        }

        if (is_string($this->indexBy) && count($this->select) === 1) {
            if (!str_contains($this->indexBy, '.') && count($tables = $this->getTablesUsedInFrom()) > 0) {
                $this->select[] = key($tables) . '.' . $this->indexBy;
            } else {
                $this->select[] = $this->indexBy;
            }
        }

        $rows = $this->createCommand()->queryAll();
        $results = [];

        /** @psalm-var array<array-key, array<string, string>> $rows */
        foreach ($rows as $row) {
            $value = reset($row);

            if ($this->indexBy instanceof Closure) {
                $results[($this->indexBy)($row)] = $value;
            } elseif ($this->indexBy !== null) {
                $results[$row[$this->indexBy]] = $value;
            }
        }

        return $results;
    }

    /**
     * Returns the number of records.
     *
     * @param string $q the COUNT expression. Defaults to '*'.
     * Make sure you properly [quote](guide:db-dao#quoting-table-and-column-names) column names in the expression.
     *
     * @throws Exception|InvalidConfigException|Throwable
     *
     * @return bool|int|string|null number of records. The result may be a string depending on the underlying database
     * engine and to support integer values higher than a 32bit PHP integer can handle.
     */
    public function count(string $q = '*'): bool|int|null|string
    {
        if ($this->emulateExecution) {
            return 0;
        }

        return $this->queryScalar("COUNT($q)");
    }

    /**
     * Returns the sum of the specified column values.
     *
     * @param string $q the column name or expression.
     * Make sure you properly [quote](guide:db-dao#quoting-table-and-column-names) column names in the expression.
     *
     * @throws Exception|InvalidConfigException|Throwable
     *
     * @return bool|int|string|null the sum of the specified column values.
     */
    public function sum(string $q): bool|int|string|null
    {
        if ($this->emulateExecution) {
            return 0;
        }

        return $this->queryScalar("SUM($q)");
    }

    /**
     * Returns the average of the specified column values.
     *
     * @param string $q the column name or expression.
     * Make sure you properly [quote](guide:db-dao#quoting-table-and-column-names) column names in the expression.
     *
     * @throws Throwable
     *
     * @return bool|int|string|null the average of the specified column values.
     */
    public function average(string $q): bool|int|string|null
    {
        if ($this->emulateExecution) {
            return 0;
        }

        return $this->queryScalar("AVG($q)");
    }

    /**
     * Returns the minimum of the specified column values.
     *
     * @param string $q the column name or expression.
     * Make sure you properly [quote](guide:db-dao#quoting-table-and-column-names) column names in the expression.
     *
     * @throws Exception|InvalidConfigException|Throwable
     *
     * @return bool|int|string|null the minimum of the specified column values.
     */
    public function min(string $q): bool|int|string|null
    {
        return $this->queryScalar("MIN($q)");
    }

    /**
     * Returns the maximum of the specified column values.
     *
     * @param string $q the column name or expression.
     * Make sure you properly [quote](guide:db-dao#quoting-table-and-column-names) column names in the expression.
     *
     * @throws Exception|InvalidConfigException|Throwable
     *
     * @return bool|int|string|null the maximum of the specified column values.
     */
    public function max(string $q): bool|int|string|null
    {
        return $this->queryScalar("MAX($q)");
    }

    /**
     * Returns a value indicating whether the query result contains any row of data.
     *
     * @throws Exception|InvalidConfigException|Throwable
     *
     * @return bool whether the query result contains any row of data.
     */
    public function exists(): bool
    {
        if ($this->emulateExecution) {
            return false;
        }

        $command = $this->createCommand();
        $params = $command->getParams();
        $command->setSql($this->db->getQueryBuilder()->selectExists($command->getSql()));
        $command->bindValues($params);

        return (bool) $command->queryScalar();
    }

    /**
     * Queries a scalar value by setting {@see select} first.
     *
     * Restores the value of select to make this query reusable.
     *
     * @param string|ExpressionInterface $selectExpression
     *
     * @return bool|int|string|null
     *
     * @psalm-suppress PossiblyUndefinedVariable
     *@throws Exception|InvalidConfigException|Throwable
     *
     */
    protected function queryScalar(string|ExpressionInterface $selectExpression): bool|int|null|string
    {
        if ($this->emulateExecution) {
            return null;
        }

        if (
            !$this->distinct
            && empty($this->groupBy)
            && empty($this->having)
            && empty($this->union)
            && empty($this->with)
        ) {
            $select = $this->select;
            $order = $this->orderBy;
            $limit = $this->limit;
            $offset = $this->offset;

            $this->select = [$selectExpression];
            $this->orderBy = [];
            $this->limit = null;
            $this->offset = null;

            try {
                $command = $this->createCommand();
            } catch (Throwable $e) {
                /** throw it later */
            }

            $this->select = $select;
            $this->orderBy = $order;
            $this->limit = $limit;
            $this->offset = $offset;

            if (isset($e)) {
                throw $e;
            }

            return $command->queryScalar();
        }

        $query = (new self($this->db))->select($selectExpression)->from(['c' => $this]);
        [$sql, $params] = $this->db->getQueryBuilder()->build($query);
        $command = $this->db->createCommand($sql, $params);
        $this->setCommandCache($command);

        return $command->queryScalar();
    }

    /**
     * Returns table names used in {@see from} indexed by aliases.
     *
     * Both aliases and names are enclosed into {{ and }}.
     *
     * @throws InvalidArgumentException
     *
     * @return array table names indexed by aliases
     */
    public function getTablesUsedInFrom(): array
    {
        return empty($this->from) ? [] : $this->cleanUpTableNames($this->from);
    }

    /**
     * Clean up table names and aliases.
     *
     * Both aliases and names are enclosed into {{ and }}.
     *
     * @param array $tableNames non-empty array
     *
     * @throws InvalidArgumentException
     *
     * @psalm-return array<array-key, ExpressionInterface|string> table names indexed by aliases
     */
    protected function cleanUpTableNames(array $tableNames): array
    {
        $cleanedUpTableNames = [];
        $pattern = <<<PATTERN
        ~^\s*((?:['"`\[]|{{).*?(?:['"`\]]|}})|\(.*?\)|.*?)(?:(?:\s+(?:as)?\s*)((?:['"`\[]|{{).*?(?:['"`\]]|}})|.*?))?\s*$~iux
        PATTERN;

        /** @psalm-var array<array-key, Expression|string> $tableNames */
        foreach ($tableNames as $alias => $tableName) {
            if (is_string($tableName) && !is_string($alias)) {
                if (preg_match($pattern, $tableName, $matches)) {
                    if (isset($matches[2])) {
                        [, $tableName, $alias] = $matches;
                    } else {
                        $tableName = $alias = $matches[1];
                    }
                }
            }

            if (!is_string($alias)) {
                throw new InvalidArgumentException(
                    'To use Expression in from() method, pass it in array format with alias.'
                );
            }

            if ($tableName instanceof Expression) {
                $cleanedUpTableNames[$this->ensureNameQuoted($alias)] = $tableName;
            } elseif ($tableName instanceof self) {
                $cleanedUpTableNames[$this->ensureNameQuoted($alias)] = $tableName;
            } else {
                $cleanedUpTableNames[$this->ensureNameQuoted($alias)] = $this->ensureNameQuoted((string) $tableName);
            }
        }

        return $cleanedUpTableNames;
    }

    /**
     * Ensures name is wrapped with {{ and }}.
     *
     * @param string $name
     *
     * @return string
     */
    private function ensureNameQuoted(string $name): string
    {
        $name = str_replace(["'", '"', '`', '[', ']'], '', $name);

        if ($name && !preg_match('/^{{.*}}$/', $name)) {
            return '{{' . $name . '}}';
        }

        return $name;
    }

    /**
     * Sets the SELECT part of the query.
     *
     * @param array|string|ExpressionInterface $columns the columns to be selected.
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
     * @return $this the query object itself.
     */
    public function select(array|string|ExpressionInterface $columns, ?string $option = null): self
    {
        $this->select = $this->normalizeSelect($columns);
        $this->selectOption = $option;

        return $this;
    }

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
     * @param array|string|ExpressionInterface $columns the columns to add to the select. See {@see select()} for more
     * details about the format of this parameter.
     *
     * @return $this the query object itself.
     *
     * {@see select()}
     */
    public function addSelect(array|string|ExpressionInterface $columns): self
    {
        if ($this->select === []) {
            return $this->select($columns);
        }

        $this->select = array_merge($this->select, $this->normalizeSelect($columns));

        return $this;
    }

    /**
     * Normalizes the SELECT columns passed to {@see select()} or {@see addSelect()}.
     *
     * @param array|ExpressionInterface|string $columns
     *
     * @return array
     */
    protected function normalizeSelect(array|ExpressionInterface|string $columns): array
    {
        if ($columns instanceof ExpressionInterface) {
            $columns = [$columns];
        } elseif (!is_array($columns)) {
            $columns = preg_split('/\s*,\s*/', trim($columns), -1, PREG_SPLIT_NO_EMPTY);
        }

        $select = [];

        /** @psalm-var array<array-key, ExpressionInterface|string> $columns */
        foreach ($columns as $columnAlias => $columnDefinition) {
            if (is_string($columnAlias)) {
                // Already in the normalized format, good for them.
                $select[$columnAlias] = $columnDefinition;
                continue;
            }

            if (is_string($columnDefinition)) {
                if (
                    preg_match('/^(.*?)(?i:\s+as\s+|\s+)([\w\-_.]+)$/', $columnDefinition, $matches) &&
                    !preg_match('/^\d+$/', $matches[2]) &&
                    !str_contains($matches[2], '.')
                ) {
                    /** Using "columnName as alias" or "columnName alias" syntax */
                    $select[$matches[2]] = $matches[1];
                    continue;
                }
                if (!str_contains($columnDefinition, '(')) {
                    /** Normal column name, just alias it to itself to ensure it's not selected twice */
                    $select[$columnDefinition] = $columnDefinition;
                    continue;
                }
            }

            // Either a string calling a function, DB expression, or sub-query
            /** @var string */
            $select[] = $columnDefinition;
        }

        return $select;
    }

    /**
     * Sets the value indicating whether to SELECT DISTINCT or not.
     *
     * @param bool $value whether to SELECT DISTINCT or not.
     *
     * @return $this the query object itself
     */
    public function distinct(?bool $value = true): self
    {
        $this->distinct = $value;

        return $this;
    }

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
     * @return $this the query object itself
     */
    public function from(array|ExpressionInterface|string $tables): self
    {
        if ($tables instanceof ExpressionInterface) {
            $tables = [$tables];
        }

        if (is_string($tables)) {
            $tables = preg_split('/\s*,\s*/', trim($tables), -1, PREG_SPLIT_NO_EMPTY);
        }

        $this->from = $tables;

        return $this;
    }

    /**
     * Sets the WHERE part of the query.
     *
     * The method requires a `$condition` parameter, and optionally a `$params` parameter specifying the values to be
     * bound to the query.
     *
     * The `$condition` parameter should be either a string (e.g. `'id=1'`) or an array.
     *
     * {@inheritdoc}
     *
     * @param array|string|ExpressionInterface|null $condition the conditions that should be put in the WHERE part.
     * @param array $params the parameters (name => value) to be bound to the query.
     *
     * @return $this the query object itself.
     *
     * {@see andWhere()}
     * {@see orWhere()}
     * {@see QueryInterface::where()}
     */
    public function where(array|string|ExpressionInterface|null $condition, array $params = []): self
    {
        $this->where = $condition;
        $this->addParams($params);

        return $this;
    }

    /**
     * Adds WHERE condition to the existing one.
     *
     * The new condition and the existing one will be joined using the `AND` operator.
     *
     * @param array|ExpressionInterface|string $condition the new WHERE condition. Please refer to {@see where()} on how
     * to specify this parameter.
     * @param array $params the parameters (name => value) to be bound to the query.
     *
     * @return $this the query object itself.
     *
     * {@see where()}
     * {@see orWhere()}
     */
    public function andWhere($condition, array $params = []): self
    {
        if ($this->where === null) {
            $this->where = $condition;
        } elseif (is_array($this->where) && isset($this->where[0]) && strcasecmp((string) $this->where[0], 'and') === 0) {
            $this->where[] = $condition;
        } else {
            $this->where = ['and', $this->where, $condition];
        }

        $this->addParams($params);

        return $this;
    }

    /**
     * Adds WHERE condition to the existing one.
     *
     * The new condition and the existing one will be joined using the `OR` operator.
     *
     * @param array|ExpressionInterface|string $condition the new WHERE condition. Please refer to {@see where()} on how
     * to specify this parameter.
     * @param array $params the parameters (name => value) to be bound to the query.
     *
     * @return $this the query object itself.
     *
     * {@see where()}
     * {@see andWhere()}
     */
    public function orWhere($condition, array $params = []): self
    {
        if ($this->where === null) {
            $this->where = $condition;
        } else {
            $this->where = ['or', $this->where, $condition];
        }

        $this->addParams($params);

        return $this;
    }

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
     * @return $this the query object itself.
     */
    public function andFilterCompare(string $name, ?string $value, string $defaultOperator = '='): self
    {
        if (preg_match('/^(<>|>=|>|<=|<|=)/', (string) $value, $matches)) {
            $operator = $matches[1];
            $value = substr((string) $value, strlen($operator));
        } else {
            $operator = $defaultOperator;
        }

        return $this->andFilterWhere([$operator, $name, $value]);
    }

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
     * @return $this the query object itself.
     */
    public function join(string $type, array|string $table, array|string $on = '', array $params = []): self
    {
        $this->join[] = [$type, $table, $on];

        return $this->addParams($params);
    }

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
     * @return $this the query object itself.
     */
    public function innerJoin(array|string $table, array|string $on = '', array $params = []): self
    {
        $this->join[] = ['INNER JOIN', $table, $on];

        return $this->addParams($params);
    }

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
     * @return $this the query object itself.
     */
    public function leftJoin(array|string $table, array|string $on = '', array $params = []): self
    {
        $this->join[] = ['LEFT JOIN', $table, $on];

        return $this->addParams($params);
    }

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
     * @return $this the query object itself.
     */
    public function rightJoin(array|string $table, array|string $on = '', array $params = []): self
    {
        $this->join[] = ['RIGHT JOIN', $table, $on];

        return $this->addParams($params);
    }

    /**
     * Sets the GROUP BY part of the query.
     *
     * @param array|string|ExpressionInterface $columns the columns to be grouped by.
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
    public function groupBy(array|string|ExpressionInterface $columns): self
    {
        if ($columns instanceof ExpressionInterface) {
            $columns = [$columns];
        } elseif (!is_array($columns)) {
            $columns = preg_split('/\s*,\s*/', trim($columns), -1, PREG_SPLIT_NO_EMPTY);
        }
        $this->groupBy = $columns;

        return $this;
    }

    /**
     * Adds additional group-by columns to the existing ones.
     *
     * @param array|string|ExpressionInterface $columns additional columns to be grouped by.
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
    public function addGroupBy(array|string|ExpressionInterface $columns): self
    {
        if ($columns instanceof ExpressionInterface) {
            $columns = [$columns];
        } elseif (!is_array($columns)) {
            $columns = preg_split('/\s*,\s*/', trim($columns), -1, PREG_SPLIT_NO_EMPTY);
        }

        if ($this->groupBy === []) {
            $this->groupBy = $columns;
        } else {
            $this->groupBy = array_merge($this->groupBy, $columns);
        }

        return $this;
    }

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
    public function having(array|ExpressionInterface|string|null $condition, array $params = []): self
    {
        $this->having = $condition;
        $this->addParams($params);

        return $this;
    }

    /**
     * Adds HAVING condition to the existing one.
     * The new condition and the existing one will be joined using the `AND` operator.
     *
     * @param array|string|ExpressionInterface $condition the new HAVING condition. Please refer to {@see where()}
     * on how to specify this parameter.
     * @param array $params the parameters (name => value) to be bound to the query.
     *
     * @return $this the query object itself.
     *
     * {@see having()}
     * {@see orHaving()}
     */
    public function andHaving(array|string|ExpressionInterface $condition, array $params = []): self
    {
        if ($this->having === null) {
            $this->having = $condition;
        } else {
            $this->having = ['and', $this->having, $condition];
        }

        $this->addParams($params);

        return $this;
    }

    /**
     * Adds HAVING condition to the existing one.
     *
     * The new condition and the existing one will be joined using the `OR` operator.
     *
     * @param array|string|ExpressionInterface $condition the new HAVING condition. Please refer to {@see where()}
     * on how to specify this parameter.
     * @param array $params the parameters (name => value) to be bound to the query.
     *
     * @return $this the query object itself.
     *
     * {@see having()}
     * {@see andHaving()}
     */
    public function orHaving(array|string|ExpressionInterface $condition, array $params = []): self
    {
        if ($this->having === null) {
            $this->having = $condition;
        } else {
            $this->having = ['or', $this->having, $condition];
        }

        $this->addParams($params);

        return $this;
    }

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
    public function filterHaving(array $condition): self
    {
        $condition = $this->filterCondition($condition);

        if ($condition !== []) {
            $this->having($condition);
        }

        return $this;
    }

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
    public function andFilterHaving(array $condition): self
    {
        $condition = $this->filterCondition($condition);

        if ($condition !== []) {
            $this->andHaving($condition);
        }

        return $this;
    }

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
    public function orFilterHaving(array $condition): self
    {
        $condition = $this->filterCondition($condition);

        if ($condition !== []) {
            $this->orHaving($condition);
        }

        return $this;
    }

    /**
     * Appends a SQL statement using UNION operator.
     *
     * @param string|Query $sql the SQL statement to be appended using UNION.
     * @param bool $all TRUE if using UNION ALL and FALSE if using UNION.
     *
     * @return $this the query object itself.
     */
    public function union(Query|string $sql, bool $all = false): self
    {
        $this->union[] = ['query' => $sql, 'all' => $all];

        return $this;
    }

    /**
     * Sets the parameters to be bound to the query.
     *
     * @param array $params list of query parameter values indexed by parameter placeholders.
     * For example, `[':name' => 'Dan', ':age' => 31]`.
     *
     * @return $this the query object itself.
     *
     * {@see addParams()}
     */
    public function params(array $params): self
    {
        $this->params = $params;

        return $this;
    }

    /**
     * Adds additional parameters to be bound to the query.
     *
     * @param array $params list of query parameter values indexed by parameter placeholders.
     * For example, `[':name' => 'Dan', ':age' => 31]`.
     *
     * @return $this the query object itself.
     *
     * {@see params()}
     */
    public function addParams(array $params): self
    {
        if (!empty($params)) {
            if (empty($this->params)) {
                $this->params = $params;
            } else {
                /**
                 * @psalm-var array $params
                 * @psalm-var mixed $value
                 */
                foreach ($params as $name => $value) {
                    if (is_int($name)) {
                        $this->params[] = $value;
                    } else {
                        $this->params[$name] = $value;
                    }
                }
            }
        }

        return $this;
    }

    /**
     * Enables query cache for this Query.
     *
     * @param int|null $duration the number of seconds that query results can remain valid in cache.
     * Use 0 to indicate that the cached data will never expire.
     * Use a negative number to indicate that query cache should not be used.
     * @param Dependency|null $dependency the cache dependency associated with the cached result.
     *
     * @return $this the Query object itself.
     *
     * @todo Check if this method @darkdef
     */
    public function cache(?int $duration = 3600, ?Dependency $dependency = null): self
    {
        $this->queryCacheDuration = $duration;
        $this->queryCacheDependency = $dependency;

        return $this;
    }

    /**
     * Disables query cache for this Query.
     *
     * @return $this the Query object itself.
     */
    public function noCache(): self
    {
        $this->queryCacheDuration = -1;
        return $this;
    }

    /**
     * Sets $command cache, if this query has enabled caching.
     *
     * @param CommandInterface $command The command instance.
     *
     * @return CommandInterface
     */
    protected function setCommandCache(CommandInterface $command): CommandInterface
    {
        if ($this->queryCacheDuration !== null || $this->queryCacheDependency !== null) {
            $command->cache($this->queryCacheDuration, $this->queryCacheDependency);
        }

        return $command;
    }

    /**
     * Returns the SQL representation of Query.
     *
     * @return string
     */
    public function __toString(): string
    {
        return serialize($this);
    }

    public function getDistinct(): ?bool
    {
        return $this->distinct;
    }

    public function getFrom(): array|null
    {
        return $this->from;
    }

    public function getGroupBy(): array
    {
        return $this->groupBy;
    }

    public function getHaving(): string|array|ExpressionInterface|null
    {
        return $this->having;
    }

    public function getJoin(): array
    {
        return $this->join;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    public function getSelect(): array
    {
        return $this->select;
    }

    public function getSelectOption(): ?string
    {
        return $this->selectOption;
    }

    public function getUnion(): array
    {
        return $this->union;
    }

    public function getWithQueries(): array
    {
        return $this->withQueries;
    }

    /**
     * Prepends a SQL statement using WITH syntax.
     *
     * @param string|Query $query the SQL statement to be appended using UNION.
     * @param string $alias query alias in WITH construction.
     * @param bool $recursive TRUE if using WITH RECURSIVE and FALSE if using WITH.
     *
     * @return $this the query object itself.
     */
    public function withQuery(Query|string $query, string $alias, bool $recursive = false): self
    {
        $this->withQueries[] = ['query' => $query, 'alias' => $alias, 'recursive' => $recursive];

        return $this;
    }

    public function selectOption(?string $value): self
    {
        $this->selectOption = $value;

        return $this;
    }

    public function setJoin(array $value): self
    {
        $this->join = $value;

        return $this;
    }

    public function setUnion(array $value): self
    {
        $this->union = $value;

        return $this;
    }

    public function withQueries(array $value): self
    {
        $this->withQueries = $value;

        return $this;
    }
}
