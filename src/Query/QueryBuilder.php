<?php

declare(strict_types=1);

namespace Yiisoft\Db\Query;

use Generator;
use JsonException;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Constraint\Constraint;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Expression\ExpressionBuilder;
use Yiisoft\Db\Expression\ExpressionBuilderInterface;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Pdo\PdoValue;
use Yiisoft\Db\Pdo\PdoValueBuilder;
use Yiisoft\Db\Query\Conditions\ConditionInterface;
use Yiisoft\Db\Query\Conditions\HashCondition;
use Yiisoft\Db\Query\Conditions\SimpleCondition;
use Yiisoft\Db\Schema\ColumnSchemaBuilder;
use Yiisoft\Db\Schema\Schema;
use Yiisoft\Strings\NumericHelper;

use function array_combine;
use function array_diff;
use function array_filter;
use function array_keys;
use function array_map;
use function array_merge;
use function array_reverse;
use function array_shift;
use function array_unique;
use function array_values;
use function count;
use function ctype_digit;
use function get_class;
use function implode;
use function in_array;
use function is_array;
use function is_int;
use function is_object;
use function is_string;
use function is_subclass_of;
use function json_encode;
use function ltrim;
use function preg_match;
use function preg_replace;
use function preg_split;
use function reset;
use function strpos;
use function strtoupper;
use function strtr;
use function trim;

/**
 * QueryBuilder builds a SELECT SQL statement based on the specification given as a {@see Query} object.
 *
 * SQL statements are created from {@see Query} objects using the {@see build()}-method.
 *
 * QueryBuilder is also used by {@see Command} to build SQL statements such as INSERT, UPDATE, DELETE, CREATE TABLE.
 *
 * For more details and usage information on QueryBuilder:
 * {@see [guide article on query builders](guide:db-query-builder)}.
 *
 * @property string[] $conditionClasses Map of condition aliases to condition classes. This property is write-only.
 *
 * For example:
 * ```php
 *     ['LIKE' => \Yiisoft\Db\Condition\LikeCondition::class]
 * ```
 * @property string[] $expressionBuilders Array of builders that should be merged with the pre-defined ones in
 * {@see expressionBuilders} property. This property is write-only.
 */
class QueryBuilder
{
    /**
     * The prefix for automatically generated query binding parameters.
     */
    public const PARAM_PREFIX = ':qp';

    /**
     * @var array the abstract column types mapped to physical column types.
     * This is mainly used to support creating/modifying tables using DB-independent data type specifications.
     * Child classes should override this property to declare supported type mappings.
     *
     * @psalm-var array<string, string>
     */
    protected array $typeMap = [];

    /**
     * @var array map of condition aliases to condition classes. For example:
     *
     * ```php
     * return [
     *     'LIKE' => \Yiisoft\Db\Condition\LikeCondition::class,
     * ];
     * ```
     *
     * This property is used by {@see createConditionFromArray} method.
     * See default condition classes list in {@see defaultConditionClasses()} method.
     *
     * In case you want to add custom conditions support, use the {@see setConditionClasses()} method.
     *
     * @see setConditonClasses()
     * @see defaultConditionClasses()
     */
    protected array $conditionClasses = [];

    /**
     * @var ExpressionBuilderInterface[]|string[] maps expression class to expression builder class.
     * For example:
     *
     * ```php
     * [
     *    Expression::class => ExpressionBuilder::class
     * ]
     * ```
     * This property is mainly used by {@see buildExpression()} to build SQL expressions form expression objects.
     * See default values in {@see defaultExpressionBuilders()} method.
     *
     * @see setExpressionBuilders()
     * @see defaultExpressionBuilders()
     */
    protected array $expressionBuilders = [];
    protected string $separator = ' ';
    private ConnectionInterface $db;

    public function __construct(ConnectionInterface $db)
    {
        $this->db = $db;
        $this->expressionBuilders = $this->defaultExpressionBuilders();
        $this->conditionClasses = $this->defaultConditionClasses();
    }

    /**
     * Contains array of default condition classes. Extend this method, if you want to change default condition classes
     * for the query builder.
     *
     * @return array
     *
     * See {@see conditionClasses} docs for details.
     */
    protected function defaultConditionClasses(): array
    {
        return [
            'NOT' => Conditions\NotCondition::class,
            'AND' => Conditions\AndCondition::class,
            'OR' => Conditions\OrCondition::class,
            'BETWEEN' => Conditions\BetweenCondition::class,
            'NOT BETWEEN' => Conditions\BetweenCondition::class,
            'IN' => Conditions\InCondition::class,
            'NOT IN' => Conditions\InCondition::class,
            'LIKE' => Conditions\LikeCondition::class,
            'NOT LIKE' => Conditions\LikeCondition::class,
            'OR LIKE' => Conditions\LikeCondition::class,
            'OR NOT LIKE' => Conditions\LikeCondition::class,
            'EXISTS' => Conditions\ExistsCondition::class,
            'NOT EXISTS' => Conditions\ExistsCondition::class,
        ];
    }

    /**
     * Contains array of default expression builders. Extend this method and override it, if you want to change default
     * expression builders for this query builder.
     *
     * @return array
     *
     * See {@see expressionBuilders} docs for details.
     */
    protected function defaultExpressionBuilders(): array
    {
        return [
            Query::class => QueryExpressionBuilder::class,
            PdoValue::class => PdoValueBuilder::class,
            Expression::class => ExpressionBuilder::class,
            Conditions\ConjunctionCondition::class => Conditions\ConjunctionConditionBuilder::class,
            Conditions\NotCondition::class => Conditions\NotConditionBuilder::class,
            Conditions\AndCondition::class => Conditions\ConjunctionConditionBuilder::class,
            Conditions\OrCondition::class => Conditions\ConjunctionConditionBuilder::class,
            Conditions\BetweenCondition::class => Conditions\BetweenConditionBuilder::class,
            Conditions\InCondition::class => Conditions\InConditionBuilder::class,
            Conditions\LikeCondition::class => Conditions\LikeConditionBuilder::class,
            Conditions\ExistsCondition::class => Conditions\ExistsConditionBuilder::class,
            Conditions\SimpleCondition::class => Conditions\SimpleConditionBuilder::class,
            Conditions\HashCondition::class => Conditions\HashConditionBuilder::class,
            Conditions\BetweenColumnsCondition::class => Conditions\BetweenColumnsConditionBuilder::class,
        ];
    }

    /**
     * Setter for {@see expressionBuilders property.
     *
     * @param string[] $builders array of builders that should be merged with the pre-defined ones in property.
     *
     * See {@see expressionBuilders} docs for details.
     */
    public function setExpressionBuilders(array $builders): void
    {
        $this->expressionBuilders = array_merge($this->expressionBuilders, $builders);
    }

    /**
     * Setter for {@see conditionClasses} property.
     *
     * @param string[] $classes map of condition aliases to condition classes. For example:
     *
     * ```php
     * ['LIKE' => \Yiisoft\Db\Condition\LikeCondition::class]
     * ```
     *
     * See {@see conditionClasses} docs for details.
     */
    public function setConditionClasses(array $classes): void
    {
        $this->conditionClasses = array_merge($this->conditionClasses, $classes);
    }

    /**
     * Generates a SELECT SQL statement from a {@see Query} object.
     *
     * @param Query $query the {@see Query} object from which the SQL statement will be generated.
     * @param array $params the parameters to be bound to the generated SQL statement. These parameters will be included
     * in the result with the additional parameters generated during the query building process.
     *
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException
     *
     * @return array the generated SQL statement (the first array element) and the corresponding parameters to be bound
     * to the SQL statement (the second array element). The parameters returned include those provided in `$params`.
     *
     * @psalm-return array{0: string, 1: array}
     */
    public function build(Query $query, array $params = []): array
    {
        $query = $query->prepare($this);

        $params = empty($params) ? $query->getParams() : array_merge($params, $query->getParams());

        $clauses = [
            $this->buildSelect($query->getSelect(), $params, $query->getDistinct(), $query->getSelectOption()),
            $this->buildFrom($query->getFrom(), $params),
            $this->buildJoin($query->getJoin(), $params),
            $this->buildWhere($query->getWhere(), $params),
            $this->buildGroupBy($query->getGroupBy(), $params),
            $this->buildHaving($query->getHaving(), $params),
        ];

        $sql = implode($this->separator, array_filter($clauses));

        $sql = $this->buildOrderByAndLimit($sql, $query->getOrderBy(), $query->getLimit(), $query->getOffset());

        if (!empty($query->getOrderBy())) {
            foreach ($query->getOrderBy() as $expression) {
                if ($expression instanceof ExpressionInterface) {
                    $this->buildExpression($expression, $params);
                }
            }
        }

        if (!empty($query->getGroupBy())) {
            foreach ($query->getGroupBy() as $expression) {
                if ($expression instanceof ExpressionInterface) {
                    $this->buildExpression($expression, $params);
                }
            }
        }

        $union = $this->buildUnion($query->getUnion(), $params);

        if ($union !== '') {
            $sql = "($sql){$this->separator}$union";
        }

        $with = $this->buildWithQueries($query->getWithQueries(), $params);

        if ($with !== '') {
            $sql = "$with{$this->separator}$sql";
        }

        return [$sql, $params];
    }

    /**
     * Builds given $expression.
     *
     * @param ExpressionInterface $expression the expression to be built
     * @param array $params the parameters to be bound to the generated SQL statement. These parameters will be included
     * in the result with the additional parameters generated during the expression building process.
     *
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException when $expression building
     * is not supported by this QueryBuilder.
     *
     * @return string the SQL statement that will not be neither quoted nor encoded before passing to DBMS.
     *
     * @see ExpressionInterface
     * @see ExpressionBuilderInterface
     * @see expressionBuilders
     */
    public function buildExpression(ExpressionInterface $expression, array &$params = []): string
    {
        $builder = $this->getExpressionBuilder($expression);

        return (string) $builder->build($expression, $params);
    }

    /**
     * Gets object of {@see ExpressionBuilderInterface} that is suitable for $expression.
     *
     * Uses {@see expressionBuilders} array to find a suitable builder class.
     *
     * @param ExpressionInterface $expression
     *
     * @throws InvalidArgumentException when $expression building is not supported by this QueryBuilder.
     *
     * @return ExpressionBuilderInterface|QueryBuilder|string
     *
     * @see expressionBuilders
     */
    public function getExpressionBuilder(ExpressionInterface $expression)
    {
        $className = get_class($expression);

        if (!isset($this->expressionBuilders[$className])) {
            foreach (array_reverse($this->expressionBuilders) as $expressionClass => $builderClass) {
                if (is_subclass_of($expression, $expressionClass)) {
                    $this->expressionBuilders[$className] = $builderClass;
                    break;
                }
            }

            if (!isset($this->expressionBuilders[$className])) {
                throw new InvalidArgumentException(
                    'Expression of class ' . $className . ' can not be built in ' . static::class
                );
            }
        }

        if ($this->expressionBuilders[$className] === __CLASS__) {
            return $this;
        }

        if (!is_object($this->expressionBuilders[$className])) {
            $this->expressionBuilders[$className] = new $this->expressionBuilders[$className]($this);
        }

        return $this->expressionBuilders[$className];
    }

    /**
     * Creates an INSERT SQL statement.
     *
     * For example,.
     *
     * ```php
     * $sql = $queryBuilder->insert('user', [
     *     'name' => 'Sam',
     *     'age' => 30,
     * ], $params);
     * ```
     *
     * The method will properly escape the table and column names.
     *
     * @param string $table the table that new rows will be inserted into.
     * @param array|Query $columns the column data (name => value) to be inserted into the table or instance of
     * {@see Query} to perform INSERT INTO ... SELECT SQL statement. Passing of {@see Query}.
     * @param array $params the binding parameters that will be generated by this method. They should be bound to the
     * DB command later.
     *
     * @throws Exception|InvalidArgumentException|InvalidConfigException|JsonException|NotSupportedException
     *
     * @return string the INSERT SQL.
     */
    public function insert(string $table, $columns, array &$params = []): string
    {
        [$names, $placeholders, $values, $params] = $this->prepareInsertValues($table, $columns, $params);

        return 'INSERT INTO ' . $this->db->quoteTableName($table)
            . (!empty($names) ? ' (' . implode(', ', $names) . ')' : '')
            . (!empty($placeholders) ? ' VALUES (' . implode(', ', $placeholders) . ')' : $values);
    }

    /**
     * Prepares a `VALUES` part for an `INSERT` SQL statement.
     *
     * @param string $table the table that new rows will be inserted into.
     * @param array|Query $columns the column data (name => value) to be inserted into the table or instance of
     * {@see Query} to perform INSERT INTO ... SELECT SQL statement.
     * @param array $params the binding parameters that will be generated by this method.
     * They should be bound to the DB command later.
     *
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException
     *
     * @return array array of column names, placeholders, values and params.
     */
    protected function prepareInsertValues(string $table, $columns, array $params = []): array
    {
        $schema = $this->db->getSchema();
        $tableSchema = $schema->getTableSchema($table);
        $columnSchemas = $tableSchema !== null ? $tableSchema->getColumns() : [];
        $names = [];
        $placeholders = [];
        $values = ' DEFAULT VALUES';

        if ($columns instanceof Query) {
            [$names, $values, $params] = $this->prepareInsertSelectSubQuery($columns, $schema, $params);
        } else {
            foreach ($columns as $name => $value) {
                $names[] = $schema->quoteColumnName($name);
                $value = isset($columnSchemas[$name]) ? $columnSchemas[$name]->dbTypecast($value) : $value;

                if ($value instanceof ExpressionInterface) {
                    $placeholders[] = $this->buildExpression($value, $params);
                } elseif ($value instanceof Query) {
                    [$sql, $params] = $this->build($value, $params);
                    $placeholders[] = "($sql)";
                } else {
                    $placeholders[] = $this->bindParam($value, $params);
                }
            }
        }

        return [$names, $placeholders, $values, $params];
    }

    /**
     * Prepare select-subquery and field names for INSERT INTO ... SELECT SQL statement.
     *
     * @param Query $columns Object, which represents select query.
     * @param Schema $schema Schema object to quote column name.
     * @param array $params the parameters to be bound to the generated SQL statement. These parameters will be included
     * in the result with the additional parameters generated during the query building process.
     *
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException
     *
     * @return array array of column names, values and params.
     */
    protected function prepareInsertSelectSubQuery(Query $columns, Schema $schema, array $params = []): array
    {
        if (
            !is_array($columns->getSelect())
            || empty($columns->getSelect())
            || in_array('*', $columns->getSelect(), true)
        ) {
            throw new InvalidArgumentException('Expected select query object with enumerated (named) parameters');
        }

        [$values, $params] = $this->build($columns, $params);

        $names = [];
        $values = ' ' . $values;

        foreach ($columns->getSelect() as $title => $field) {
            if (is_string($title)) {
                $names[] = $schema->quoteColumnName($title);
            } elseif (preg_match('/^(.*?)(?i:\s+as\s+|\s+)([\w\-_.]+)$/', $field, $matches)) {
                $names[] = $schema->quoteColumnName($matches[2]);
            } else {
                $names[] = $schema->quoteColumnName($field);
            }
        }

        return [$names, $values, $params];
    }

    /**
     * Generates a batch INSERT SQL statement.
     *
     * For example,
     *
     * ```php
     * $sql = $queryBuilder->batchInsert('user', ['name', 'age'], [
     *     ['Tom', 30],
     *     ['Jane', 20],
     *     ['Linda', 25],
     * ]);
     * ```
     *
     * Note that the values in each row must match the corresponding column names.
     *
     * The method will properly escape the column names, and quote the values to be inserted.
     *
     * @param string $table the table that new rows will be inserted into.
     * @param array $columns the column names.
     * @param array|Generator $rows the rows to be batch inserted into the table.
     * @param array $params the binding parameters. This parameter exists.
     *
     * @throws Exception|InvalidArgumentException
     *
     * @return string the batch INSERT SQL statement.
     */
    public function batchInsert(string $table, array $columns, $rows, array &$params = []): string
    {
        if (empty($rows)) {
            return '';
        }

        $schema = $this->db->getSchema();


        if (($tableSchema = $schema->getTableSchema($table)) !== null) {
            $columnSchemas = $tableSchema->getColumns();
        } else {
            $columnSchemas = [];
        }

        $values = [];

        foreach ($rows as $row) {
            $vs = [];
            foreach ($row as $i => $value) {
                if (isset($columns[$i], $columnSchemas[$columns[$i]])) {
                    $value = $columnSchemas[$columns[$i]]->dbTypecast($value);
                }
                if (is_string($value)) {
                    $value = $schema->quoteValue($value);
                } elseif (is_float($value)) {
                    /* ensure type cast always has . as decimal separator in all locales */
                    $value = NumericHelper::normalize((string) $value);
                } elseif ($value === false) {
                    $value = 0;
                } elseif ($value === null) {
                    $value = 'NULL';
                } elseif ($value instanceof ExpressionInterface) {
                    $value = $this->buildExpression($value, $params);
                }
                $vs[] = $value;
            }
            $values[] = '(' . implode(', ', $vs) . ')';
        }

        if (empty($values)) {
            return '';
        }

        foreach ($columns as $i => $name) {
            $columns[$i] = $schema->quoteColumnName($name);
        }

        return 'INSERT INTO ' . $schema->quoteTableName($table)
            . ' (' . implode(', ', $columns) . ') VALUES ' . implode(', ', $values);
    }

    /**
     * Creates an SQL statement to insert rows into a database table if they do not already exist (matching unique
     * constraints), or update them if they do.
     *
     * For example,
     *
     * ```php
     * $sql = $queryBuilder->upsert('pages', [
     *     'name' => 'Front page',
     *     'url' => 'http://example.com/', // url is unique
     *     'visits' => 0,
     * ], [
     *     'visits' => new \Yiisoft\Db\Expression('visits + 1'),
     * ], $params);
     * ```
     *
     * The method will properly escape the table and column names.
     *
     * @param string $table the table that new rows will be inserted into/updated in.
     * @param array|Query $insertColumns the column data (name => value) to be inserted into the table or instance
     * of {@see Query} to perform `INSERT INTO ... SELECT` SQL statement.
     * @param array|bool $updateColumns the column data (name => value) to be updated if they already exist.
     * If `true` is passed, the column data will be updated to match the insert column data.
     * If `false` is passed, no update will be performed if the column data already exists.
     * @param array $params the binding parameters that will be generated by this method. They should be bound to the DB
     * command later.
     *
     * @throws Exception|NotSupportedException if this is not supported by the underlying DBMS.
     *
     * @return string the resulting SQL.
     */
    public function upsert(string $table, $insertColumns, $updateColumns, array &$params): string
    {
        throw new NotSupportedException($this->db->getDriverName() . ' does not support upsert statements.');
    }

    /**
     * @param string $table
     * @param array|Query $insertColumns
     * @param array|bool $updateColumns
     * @param Constraint[] $constraints this parameter recieves a matched constraint list.
     * The constraints will be unique by their column names.
     *
     * @throws Exception|JsonException
     *
     * @return array
     */
    protected function prepareUpsertColumns(string $table, $insertColumns, $updateColumns, array &$constraints = []): array
    {
        if ($insertColumns instanceof Query) {
            [$insertNames] = $this->prepareInsertSelectSubQuery($insertColumns, $this->db->getSchema());
        } else {
            $insertNames = array_map([$this->db, 'quoteColumnName'], array_keys($insertColumns));
        }

        $uniqueNames = $this->getTableUniqueColumnNames($table, $insertNames, $constraints);

        $uniqueNames = array_map([$this->db, 'quoteColumnName'], $uniqueNames);

        if ($updateColumns !== true) {
            return [$uniqueNames, $insertNames, null];
        }

        return [$uniqueNames, $insertNames, array_diff($insertNames, $uniqueNames)];
    }

    /**
     * Returns all column names belonging to constraints enforcing uniqueness (`PRIMARY KEY`, `UNIQUE INDEX`, etc.)
     * for the named table removing constraints which did not cover the specified column list.
     *
     * The column list will be unique by column names.
     *
     * @param string $name table name. The table name may contain schema name if any. Do not quote the table name.
     * @param string[] $columns source column list.
     * @param Constraint[] $constraints this parameter optionally recieves a matched constraint list. The constraints
     * will be unique by their column names.
     *
     * @throws JsonException
     *
     * @return array column list.
     */
    private function getTableUniqueColumnNames(string $name, array $columns, array &$constraints = []): array
    {
        $schema = $this->db->getSchema();

        $constraints = [];
        $primaryKey = $schema->getTablePrimaryKey($name);

        if ($primaryKey !== null) {
            $constraints[] = $primaryKey;
        }

        foreach ($schema->getTableIndexes($name) as $constraint) {
            if ($constraint->isUnique()) {
                $constraints[] = $constraint;
            }
        }

        $constraints = array_merge($constraints, $schema->getTableUniques($name));

        /** Remove duplicates */
        $constraints = array_combine(
            array_map(
                static function ($constraint) {
                    $columns = $constraint->getColumnNames();
                    sort($columns, SORT_STRING);

                    return json_encode($columns, JSON_THROW_ON_ERROR);
                },
                $constraints
            ),
            $constraints
        );

        $columnNames = [];

        /** Remove all constraints which do not cover the specified column list */
        $constraints = array_values(
            array_filter(
                $constraints,
                static function ($constraint) use ($schema, $columns, &$columnNames) {
                    /** @psalm-suppress UndefinedClass, UndefinedMethod */
                    $constraintColumnNames = array_map([$schema, 'quoteColumnName'], $constraint->getColumnNames());
                    $result = !array_diff($constraintColumnNames, $columns);

                    if ($result) {
                        $columnNames = array_merge($columnNames, $constraintColumnNames);
                    }

                    return $result;
                }
            )
        );

        return array_unique($columnNames);
    }

    /**
     * Creates an UPDATE SQL statement.
     *
     * For example,
     *
     * ```php
     * $params = [];
     * $sql = $queryBuilder->update('user', ['status' => 1], 'age > 30', $params);
     * ```
     *
     * The method will properly escape the table and column names.
     *
     * @param string $table the table to be updated.
     * @param array $columns the column data (name => value) to be updated.
     * @param array|string $condition the condition that will be put in the WHERE part. Please refer to
     * {@see Query::where()} on how to specify condition.
     * @param array $params the binding parameters that will be modified by this method so that they can be bound to the
     * DB command later.
     *
     * @psalm-param array<string, ExpressionInterface|string> $columns
     *
     * @throws Exception|InvalidArgumentException
     *
     * @return string the UPDATE SQL.
     */
    public function update(string $table, array $columns, $condition, array &$params = []): string
    {
        /**
         * @psalm-var array<array-key, mixed> $lines
         * @psalm-var array<array-key, mixed> $params
         */
        [$lines, $params] = $this->prepareUpdateSets($table, $columns, $params);
        $sql = 'UPDATE ' . $this->db->quoteTableName($table) . ' SET ' . implode(', ', $lines);
        $where = $this->buildWhere($condition, $params);

        return ($where === '') ? $sql : ($sql . ' ' . $where);
    }

    /**
     * Prepares a `SET` parts for an `UPDATE` SQL statement.
     *
     * @param string $table the table to be updated.
     * @param array $columns the column data (name => value) to be updated.
     * @param array $params the binding parameters that will be modified by this method so that they can be bound to the
     * DB command later.
     *
     * @psalm-param array<string, ExpressionInterface|string> $columns
     *
     * @throws Exception|InvalidArgumentException
     *
     * @return array `SET` parts for an `UPDATE` SQL statement (the first array element) and params (the second array
     * element).
     */
    protected function prepareUpdateSets(string $table, array $columns, array $params = []): array
    {
        $tableSchema = $this->db->getTableSchema($table);

        $columnSchemas = $tableSchema !== null ? $tableSchema->getColumns() : [];

        $sets = [];

        foreach ($columns as $name => $value) {
            /** @psalm-var mixed $value */
            $value = isset($columnSchemas[$name]) ? $columnSchemas[$name]->dbTypecast($value) : $value;
            if ($value instanceof ExpressionInterface) {
                $placeholder = $this->buildExpression($value, $params);
            } else {
                $placeholder = $this->bindParam($value, $params);
            }

            $sets[] = $this->db->quoteColumnName($name) . '=' . $placeholder;
        }

        return [$sets, $params];
    }

    /**
     * Creates a DELETE SQL statement.
     *
     * For example,
     *
     * ```php
     * $sql = $queryBuilder->delete('user', 'status = 0');
     * ```
     *
     * The method will properly escape the table and column names.
     *
     * @param string $table the table where the data will be deleted from.
     * @param array|string $condition the condition that will be put in the WHERE part. Please refer to
     * {@see Query::where()} on how to specify condition.
     * @param array $params the binding parameters that will be modified by this method so that they can be bound to the
     * DB command later.
     *
     * @throws Exception|InvalidArgumentException
     *
     * @return string the DELETE SQL.
     */
    public function delete(string $table, $condition, array &$params): string
    {
        $sql = 'DELETE FROM ' . $this->db->quoteTableName($table);
        $where = $this->buildWhere($condition, $params);

        return ($where === '') ? $sql : ($sql . ' ' . $where);
    }

    /**
     * Builds a SQL statement for creating a new DB table.
     *
     * The columns in the new  table should be specified as name-definition pairs (e.g. 'name' => 'string'), where name
     * stands for a column name which will be properly quoted by the method, and definition stands for the column type
     * which can contain an abstract DB type.
     *
     * The {@see getColumnType()} method will be invoked to convert any abstract type into a physical one.
     *
     * If a column is specified with definition only (e.g. 'PRIMARY KEY (name, type)'), it will be directly inserted
     * into the generated SQL.
     *
     * For example,
     *
     * ```php
     * $sql = $queryBuilder->createTable('user', [
     *  'id' => 'pk',
     *  'name' => 'string',
     *  'age' => 'integer',
     * ]);
     * ```
     *
     * @param string $table the name of the table to be created. The name will be properly quoted by the method.
     * @param array $columns the columns (name => definition) in the new table.
     * @param string|null $options additional SQL fragment that will be appended to the generated SQL.
     *
     * @psalm-param array<array-key, ColumnSchemaBuilder|string> $columns
     *
     * @return string the SQL statement for creating a new DB table.
     */
    public function createTable(string $table, array $columns, ?string $options = null): string
    {
        $cols = [];

        foreach ($columns as $name => $type) {
            if (is_string($name)) {
                $cols[] = "\t" . $this->db->quoteColumnName($name) . ' ' . $this->getColumnType($type);
            } else {
                $cols[] = "\t" . $type;
            }
        }

        $sql = 'CREATE TABLE ' . $this->db->quoteTableName($table) . " (\n" . implode(",\n", $cols) . "\n)";

        return ($options === null) ? $sql : ($sql . ' ' . $options);
    }

    /**
     * Builds a SQL statement for renaming a DB table.
     *
     * @param string $oldName the table to be renamed. The name will be properly quoted by the method.
     * @param string $newName the new table name. The name will be properly quoted by the method.
     *
     * @return string the SQL statement for renaming a DB table.
     */
    public function renameTable(string $oldName, string $newName): string
    {
        return 'RENAME TABLE ' . $this->db->quoteTableName($oldName) . ' TO ' . $this->db->quoteTableName($newName);
    }

    /**
     * Builds a SQL statement for dropping a DB table.
     *
     * @param string $table the table to be dropped. The name will be properly quoted by the method.
     *
     * @return string the SQL statement for dropping a DB table.
     */
    public function dropTable(string $table): string
    {
        return 'DROP TABLE ' . $this->db->quoteTableName($table);
    }

    /**
     * Builds a SQL statement for adding a primary key constraint to an existing table.
     *
     * @param string $name the name of the primary key constraint.
     * @param string $table the table that the primary key constraint will be added to.
     * @param array|string $columns comma separated string or array of columns that the primary key will consist of.
     *
     * @psalm-param array<array-key, string>|string $columns
     *
     * @return string the SQL statement for adding a primary key constraint to an existing table.
     */
    public function addPrimaryKey(string $name, string $table, $columns): string
    {
        if (is_string($columns)) {
            $columns = preg_split('/\s*,\s*/', $columns, -1, PREG_SPLIT_NO_EMPTY);
        }

        foreach ($columns as $i => $col) {
            $columns[$i] = $this->db->quoteColumnName($col);
        }

        return 'ALTER TABLE ' . $this->db->quoteTableName($table) . ' ADD CONSTRAINT '
            . $this->db->quoteColumnName($name) . ' PRIMARY KEY ('
            . implode(', ', $columns) . ')';
    }

    /**
     * Builds a SQL statement for removing a primary key constraint to an existing table.
     *
     * @param string $name the name of the primary key constraint to be removed.
     * @param string $table the table that the primary key constraint will be removed from.
     *
     * @return string the SQL statement for removing a primary key constraint from an existing table.
     */
    public function dropPrimaryKey(string $name, string $table): string
    {
        return 'ALTER TABLE ' . $this->db->quoteTableName($table)
            . ' DROP CONSTRAINT ' . $this->db->quoteColumnName($name);
    }

    /**
     * Builds a SQL statement for truncating a DB table.
     *
     * @param string $table the table to be truncated. The name will be properly quoted by the method.
     *
     * @return string the SQL statement for truncating a DB table.
     */
    public function truncateTable(string $table): string
    {
        return 'TRUNCATE TABLE ' . $this->db->quoteTableName($table);
    }

    /**
     * Builds a SQL statement for adding a new DB column.
     *
     * @param string $table the table that the new column will be added to. The table name will be properly quoted by
     * the method.
     * @param string $column the name of the new column. The name will be properly quoted by the method.
     * @param string $type the column type. The {@see getColumnType()} method will be invoked to convert abstract column
     * type (if any) into the physical one. Anything that is not recognized as abstract type will be kept in the
     * generated SQL.
     * For example, 'string' will be turned into 'varchar(255)', while 'string not null' will become
     * 'varchar(255) not null'.
     *
     * @return string the SQL statement for adding a new column.
     */
    public function addColumn(string $table, string $column, string $type): string
    {
        return 'ALTER TABLE ' . $this->db->quoteTableName($table)
            . ' ADD ' . $this->db->quoteColumnName($column) . ' '
            . $this->getColumnType($type);
    }

    /**
     * Builds a SQL statement for dropping a DB column.
     *
     * @param string $table the table whose column is to be dropped. The name will be properly quoted by the method.
     * @param string $column the name of the column to be dropped. The name will be properly quoted by the method.
     *
     * @return string the SQL statement for dropping a DB column.
     */
    public function dropColumn(string $table, string $column): string
    {
        return 'ALTER TABLE ' . $this->db->quoteTableName($table)
            . ' DROP COLUMN ' . $this->db->quoteColumnName($column);
    }

    /**
     * Builds a SQL statement for renaming a column.
     *
     * @param string $table the table whose column is to be renamed. The name will be properly quoted by the method.
     * @param string $oldName the old name of the column. The name will be properly quoted by the method.
     * @param string $newName the new name of the column. The name will be properly quoted by the method.
     *
     * @return string the SQL statement for renaming a DB column.
     */
    public function renameColumn(string $table, string $oldName, string $newName): string
    {
        return 'ALTER TABLE ' . $this->db->quoteTableName($table)
            . ' RENAME COLUMN ' . $this->db->quoteColumnName($oldName)
            . ' TO ' . $this->db->quoteColumnName($newName);
    }

    /**
     * Builds a SQL statement for changing the definition of a column.
     *
     * @param string $table the table whose column is to be changed. The table name will be properly quoted by the
     * method.
     * @param string $column the name of the column to be changed. The name will be properly quoted by the method.
     * @param string $type the new column type. The {@see getColumnType()} method will be invoked to convert abstract
     * column type (if any) into the physical one. Anything that is not recognized as abstract type will be kept
     * in the generated SQL. For example, 'string' will be turned into 'varchar(255)', while 'string not null'
     * will become 'varchar(255) not null'.
     *
     * @return string the SQL statement for changing the definition of a column.
     */
    public function alterColumn(string $table, string $column, string $type): string
    {
        return 'ALTER TABLE ' . $this->db->quoteTableName($table) . ' CHANGE '
            . $this->db->quoteColumnName($column) . ' '
            . $this->db->quoteColumnName($column) . ' '
            . $this->getColumnType($type);
    }

    /**
     * Builds a SQL statement for adding a foreign key constraint to an existing table. The method will properly quote
     * the table and column names.
     *
     * @param string $name the name of the foreign key constraint.
     * @param string $table the table that the foreign key constraint will be added to.
     * @param array|string $columns the name of the column to that the constraint will be added on. If there are
     * multiple columns, separate them with commas or use an array to represent them.
     * @param string $refTable the table that the foreign key references to.
     * @param array|string $refColumns the name of the column that the foreign key references to. If there are multiple
     * columns, separate them with commas or use an array to represent them.
     * @param string|null $delete the ON DELETE option. Most DBMS support these options: RESTRICT, CASCADE, NO ACTION,
     * SET DEFAULT, SET NULL.
     * @param string|null $update the ON UPDATE option. Most DBMS support these options: RESTRICT, CASCADE, NO ACTION,
     * SET DEFAULT, SET NULL.
     *
     * @psalm-param array<array-key, string>|string $columns
     * @psalm-param array<array-key, string>|string $refColumns
     *
     * @throws Exception|InvalidArgumentException
     *
     * @return string the SQL statement for adding a foreign key constraint to an existing table.
     */
    public function addForeignKey(
        string $name,
        string $table,
        $columns,
        string $refTable,
        $refColumns,
        ?string $delete = null,
        ?string $update = null
    ): string {
        $sql = 'ALTER TABLE ' . $this->db->quoteTableName($table)
            . ' ADD CONSTRAINT ' . $this->db->quoteColumnName($name)
            . ' FOREIGN KEY (' . $this->buildColumns($columns) . ')'
            . ' REFERENCES ' . $this->db->quoteTableName($refTable)
            . ' (' . $this->buildColumns($refColumns) . ')';

        if ($delete !== null) {
            $sql .= ' ON DELETE ' . $delete;
        }

        if ($update !== null) {
            $sql .= ' ON UPDATE ' . $update;
        }

        return $sql;
    }

    /**
     * Builds a SQL statement for dropping a foreign key constraint.
     *
     * @param string $name the name of the foreign key constraint to be dropped. The name will be properly quoted by
     * the method.
     * @param string $table the table whose foreign is to be dropped. The name will be properly quoted by the method.
     *
     * @return string the SQL statement for dropping a foreign key constraint.
     */
    public function dropForeignKey(string $name, string $table): string
    {
        return 'ALTER TABLE ' . $this->db->quoteTableName($table)
            . ' DROP CONSTRAINT ' . $this->db->quoteColumnName($name);
    }

    /**
     * Builds a SQL statement for creating a new index.
     *
     * @param string $name the name of the index. The name will be properly quoted by the method.
     * @param string $table the table that the new index will be created for. The table name will be properly quoted by
     * the method.
     * @param array|string $columns the column(s) that should be included in the index. If there are multiple columns,
     * separate them with commas or use an array to represent them. Each column name will be properly quoted by the
     * method, unless a parenthesis is found in the name.
     * @param bool $unique whether to add UNIQUE constraint on the created index.
     *
     * @psalm-param array<array-key, ExpressionInterface|string>|string $columns
     *
     * @throws Exception|InvalidArgumentException
     *
     * @return string the SQL statement for creating a new index.
     */
    public function createIndex(string $name, string $table, $columns, bool $unique = false): string
    {
        return ($unique ? 'CREATE UNIQUE INDEX ' : 'CREATE INDEX ')
            . $this->db->quoteTableName($name) . ' ON '
            . $this->db->quoteTableName($table)
            . ' (' . $this->buildColumns($columns) . ')';
    }

    /**
     * Builds a SQL statement for dropping an index.
     *
     * @param string $name the name of the index to be dropped. The name will be properly quoted by the method.
     * @param string $table the table whose index is to be dropped. The name will be properly quoted by the method.
     *
     * @return string the SQL statement for dropping an index.
     */
    public function dropIndex(string $name, string $table): string
    {
        return 'DROP INDEX ' . $this->db->quoteTableName($name) . ' ON ' . $this->db->quoteTableName($table);
    }

    /**
     * Creates a SQL command for adding an unique constraint to an existing table.
     *
     * @param string $name the name of the unique constraint. The name will be properly quoted by the method.
     * @param string $table the table that the unique constraint will be added to. The name will be properly quoted by
     * the method.
     * @param array|string $columns the name of the column to that the constraint will be added on. If there are
     * multiple columns, separate them with commas. The name will be properly quoted by the method.
     *
     * @psalm-param array<array-key, string>|string $columns
     *
     * @return string the SQL statement for adding an unique constraint to an existing table.
     */
    public function addUnique(string $name, string $table, $columns): string
    {
        if (is_string($columns)) {
            $columns = preg_split('/\s*,\s*/', $columns, -1, PREG_SPLIT_NO_EMPTY);
        }

        foreach ($columns as $i => $col) {
            $columns[$i] = $this->db->quoteColumnName($col);
        }

        return 'ALTER TABLE ' . $this->db->quoteTableName($table) . ' ADD CONSTRAINT '
            . $this->db->quoteColumnName($name) . ' UNIQUE ('
            . implode(', ', $columns) . ')';
    }

    /**
     * Creates a SQL command for dropping an unique constraint.
     *
     * @param string $name the name of the unique constraint to be dropped. The name will be properly quoted by the
     * method.
     * @param string $table the table whose unique constraint is to be dropped. The name will be properly quoted by the
     * method.
     *
     * @return string the SQL statement for dropping an unique constraint.
     */
    public function dropUnique(string $name, string $table): string
    {
        return 'ALTER TABLE ' . $this->db->quoteTableName($table)
            . ' DROP CONSTRAINT ' . $this->db->quoteColumnName($name);
    }

    /**
     * Creates a SQL command for adding a check constraint to an existing table.
     *
     * @param string $name the name of the check constraint. The name will be properly quoted by the method.
     * @param string $table the table that the check constraint will be added to. The name will be properly quoted by
     * the method.
     * @param string $expression the SQL of the `CHECK` constraint.
     *
     * @return string the SQL statement for adding a check constraint to an existing table.
     */
    public function addCheck(string $name, string $table, string $expression): string
    {
        return 'ALTER TABLE ' . $this->db->quoteTableName($table) . ' ADD CONSTRAINT '
            . $this->db->quoteColumnName($name) . ' CHECK (' . $this->db->quoteSql($expression) . ')';
    }

    /**
     * Creates a SQL command for dropping a check constraint.
     *
     * @param string $name the name of the check constraint to be dropped. The name will be properly quoted by the
     * method.
     * @param string $table the table whose check constraint is to be dropped. The name will be properly quoted by the
     * method.
     *
     * @return string the SQL statement for dropping a check constraint.
     */
    public function dropCheck(string $name, string $table): string
    {
        return 'ALTER TABLE ' . $this->db->quoteTableName($table)
            . ' DROP CONSTRAINT ' . $this->db->quoteColumnName($name);
    }

    /**
     * Creates a SQL command for adding a default value constraint to an existing table.
     *
     * @param string $name the name of the default value constraint.
     * The name will be properly quoted by the method.
     * @param string $table the table that the default value constraint will be added to.
     * The name will be properly quoted by the method.
     * @param string $column the name of the column to that the constraint will be added on.
     * The name will be properly quoted by the method.
     * @param mixed $value default value.
     *
     * @throws Exception|NotSupportedException if this is not supported by the underlying DBMS.
     *
     * @return string the SQL statement for adding a default value constraint to an existing table.
     */
    public function addDefaultValue(string $name, string $table, string $column, $value): string
    {
        throw new NotSupportedException(
            $this->db->getDriverName() . ' does not support adding default value constraints.'
        );
    }

    /**
     * Creates a SQL command for dropping a default value constraint.
     *
     * @param string $name the name of the default value constraint to be dropped.
     * The name will be properly quoted by the method.
     * @param string $table the table whose default value constraint is to be dropped.
     * The name will be properly quoted by the method.
     *
     * @throws Exception|NotSupportedException if this is not supported by the underlying DBMS.
     *
     * @return string the SQL statement for dropping a default value constraint.
     */
    public function dropDefaultValue(string $name, string $table): string
    {
        throw new NotSupportedException(
            $this->db->getDriverName() . ' does not support dropping default value constraints.'
        );
    }

    /**
     * Creates a SQL statement for resetting the sequence value of a table's primary key.
     *
     * The sequence will be reset such that the primary key of the next new row inserted will have the specified value
     * or 1.
     *
     * @param string $tableName the name of the table whose primary key sequence will be reset.
     * @param array|string|null $value the value for the primary key of the next new row inserted. If this is not set,
     * the next new row's primary key will have a value 1.
     *
     * @throws Exception|NotSupportedException if this is not supported by the underlying DBMS.
     *
     * @return string the SQL statement for resetting sequence.
     */
    public function resetSequence(string $tableName, $value = null): string
    {
        throw new NotSupportedException($this->db->getDriverName() . ' does not support resetting sequence.');
    }

    /**
     * Builds a SQL statement for enabling or disabling integrity check.
     *
     * @param string $schema the schema of the tables. Defaults to empty string, meaning the current or default schema.
     * @param string $table the table name. Defaults to empty string, meaning that no table will be changed.
     * @param bool $check whether to turn on or off the integrity check.
     *
     * @throws Exception|NotSupportedException if this is not supported by the underlying DBMS.
     *
     * @return string the SQL statement for checking integrity.
     */
    public function checkIntegrity(string $schema = '', string $table = '', bool $check = true): string
    {
        throw new NotSupportedException(
            $this->db->getDriverName() . ' does not support enabling/disabling integrity check.'
        );
    }

    /**
     * Builds a SQL command for adding comment to column.
     *
     * @param string $table the table whose column is to be commented. The table name will be properly quoted by the
     * method.
     * @param string $column the name of the column to be commented. The column name will be properly quoted by the
     * method.
     * @param string $comment the text of the comment to be added. The comment will be properly quoted by the method.
     *
     * @throws Exception
     *
     * @return string the SQL statement for adding comment on column.
     */
    public function addCommentOnColumn(string $table, string $column, string $comment): string
    {
        return 'COMMENT ON COLUMN ' . $this->db->quoteTableName($table) . '.' . $this->db->quoteColumnName($column)
            . ' IS ' . $this->db->quoteValue($comment);
    }

    /**
     * Builds a SQL command for adding comment to table.
     *
     * @param string $table the table whose column is to be commented. The table name will be properly quoted by the
     * method.
     * @param string $comment the text of the comment to be added. The comment will be properly quoted by the method.
     *
     * @throws Exception
     *
     * @return string the SQL statement for adding comment on table.
     */
    public function addCommentOnTable(string $table, string $comment): string
    {
        return 'COMMENT ON TABLE ' . $this->db->quoteTableName($table) . ' IS ' . $this->db->quoteValue($comment);
    }

    /**
     * Builds a SQL command for adding comment to column.
     *
     * @param string $table the table whose column is to be commented. The table name will be properly quoted by the
     * method.
     * @param string $column the name of the column to be commented. The column name will be properly quoted by the
     * method.
     *
     * @return string the SQL statement for adding comment on column.
     */
    public function dropCommentFromColumn(string $table, string $column): string
    {
        return 'COMMENT ON COLUMN ' . $this->db->quoteTableName($table) . '.' . $this->db->quoteColumnName($column)
            . ' IS NULL';
    }

    /**
     * Builds a SQL command for adding comment to table.
     *
     * @param string $table the table whose column is to be commented. The table name will be properly quoted by the
     * method.
     *
     * @return string the SQL statement for adding comment on column.
     */
    public function dropCommentFromTable(string $table): string
    {
        return 'COMMENT ON TABLE ' . $this->db->quoteTableName($table) . ' IS NULL';
    }

    /**
     * Creates a SQL View.
     *
     * @param string $viewName the name of the view to be created.
     * @param Query|string $subQuery the select statement which defines the view.
     *
     * This can be either a string or a {@see Query} object.
     *
     * @throws Exception|InvalidConfigException|NotSupportedException
     *
     * @return string the `CREATE VIEW` SQL statement.
     */
    public function createView(string $viewName, $subQuery): string
    {
        if ($subQuery instanceof Query) {
            /** @psalm-var array<array-key, int|string> $params */
            [$rawQuery, $params] = $this->build($subQuery);

            foreach ($params as $key => $value) {
                $params[$key] = $this->db->quoteValue($value);
            }

            $subQuery = strtr($rawQuery, $params);
        }

        return 'CREATE VIEW ' . $this->db->quoteTableName($viewName) . ' AS ' . $subQuery;
    }

    /**
     * Drops a SQL View.
     *
     * @param string $viewName the name of the view to be dropped.
     *
     * @return string the `DROP VIEW` SQL statement.
     */
    public function dropView(string $viewName): string
    {
        return 'DROP VIEW ' . $this->db->quoteTableName($viewName);
    }

    /**
     * Converts an abstract column type into a physical column type.
     *
     * The conversion is done using the type map specified in {@see typeMap}.
     * The following abstract column types are supported (using MySQL as an example to explain the corresponding
     * physical types):
     *
     * - `pk`: an auto-incremental primary key type, will be converted into "int(11) NOT NULL AUTO_INCREMENT PRIMARY
     *    KEY"
     * - `bigpk`: an auto-incremental primary key type, will be converted into "bigint(20) NOT NULL AUTO_INCREMENT
     *    PRIMARY KEY"
     * - `upk`: an unsigned auto-incremental primary key type, will be converted into "int(10) UNSIGNED NOT NULL
     *    AUTO_INCREMENT PRIMARY KEY"
     * - `char`: char type, will be converted into "char(1)"
     * - `string`: string type, will be converted into "varchar(255)"
     * - `text`: a long string type, will be converted into "text"
     * - `smallint`: a small integer type, will be converted into "smallint(6)"
     * - `integer`: integer type, will be converted into "int(11)"
     * - `bigint`: a big integer type, will be converted into "bigint(20)"
     * - `boolean`: boolean type, will be converted into "tinyint(1)"
     * - `float``: float number type, will be converted into "float"
     * - `decimal`: decimal number type, will be converted into "decimal"
     * - `datetime`: datetime type, will be converted into "datetime"
     * - `timestamp`: timestamp type, will be converted into "timestamp"
     * - `time`: time type, will be converted into "time"
     * - `date`: date type, will be converted into "date"
     * - `money`: money type, will be converted into "decimal(19,4)"
     * - `binary`: binary data type, will be converted into "blob"
     *
     * If the abstract type contains two or more parts separated by spaces (e.g. "string NOT NULL"), then only the first
     * part will be converted, and the rest of the parts will be appended to the converted result.
     *
     * For example, 'string NOT NULL' is converted to 'varchar(255) NOT NULL'.
     *
     * For some of the abstract types you can also specify a length or precision constraint by appending it in round
     * brackets directly to the type.
     *
     * For example `string(32)` will be converted into "varchar(32)" on a MySQL database. If the underlying DBMS does
     * not support these kind of constraints for a type it will be ignored.
     *
     * If a type cannot be found in {@see typeMap}, it will be returned without any change.
     *
     * @param ColumnSchemaBuilder|string $type abstract column type.
     *
     * @return string physical column type.
     */
    public function getColumnType($type): string
    {
        if ($type instanceof ColumnSchemaBuilder) {
            $type = $type->__toString();
        }

        if (isset($this->typeMap[$type])) {
            return $this->typeMap[$type];
        }

        if (preg_match('/^(\w+)\((.+?)\)(.*)$/', $type, $matches)) {
            if (isset($this->typeMap[$matches[1]])) {
                return preg_replace(
                    '/\(.+\)/',
                    '(' . $matches[2] . ')',
                    $this->typeMap[$matches[1]]
                ) . $matches[3];
            }
        } elseif (preg_match('/^(\w+)\s+/', $type, $matches)) {
            if (isset($this->typeMap[$matches[1]])) {
                return preg_replace('/^\w+/', $this->typeMap[$matches[1]], $type);
            }
        }

        return $type;
    }

    /**
     * @param array $columns
     * @param array $params the binding parameters to be populated.
     * @param bool|null $distinct
     * @param string|null $selectOption
     *
     * @psalm-param array<array-key, ExpressionInterface|Query|string> $columns
     *
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException
     *
     * @return string the SELECT clause built from {@see Query::$select}.
     */
    public function buildSelect(
        array $columns,
        array &$params,
        ?bool $distinct = false,
        string $selectOption = null
    ): string {
        $select = $distinct ? 'SELECT DISTINCT' : 'SELECT';

        if ($selectOption !== null) {
            $select .= ' ' . $selectOption;
        }

        if (empty($columns)) {
            return $select . ' *';
        }

        foreach ($columns as $i => $column) {
            if ($column instanceof ExpressionInterface) {
                if (is_int($i)) {
                    $columns[$i] = $this->buildExpression($column, $params);
                } else {
                    $columns[$i] = $this->buildExpression($column, $params) . ' AS ' . $this->db->quoteColumnName($i);
                }
            } elseif ($column instanceof Query) {
                [$sql, $params] = $this->build($column, $params);
                $columns[$i] = "($sql) AS " . $this->db->quoteColumnName((string) $i);
            } elseif (is_string($i) && $i !== $column) {
                if (strpos($column, '(') === false) {
                    $column = $this->db->quoteColumnName($column);
                }
                $columns[$i] = "$column AS " . $this->db->quoteColumnName($i);
            } elseif (strpos($column, '(') === false) {
                if (preg_match('/^(.*?)(?i:\s+as\s+|\s+)([\w\-_.]+)$/', $column, $matches)) {
                    $columns[$i] = $this->db->quoteColumnName(
                        $matches[1]
                    ) . ' AS ' . $this->db->quoteColumnName($matches[2]);
                } else {
                    $columns[$i] = $this->db->quoteColumnName($column);
                }
            }
        }

        return $select . ' ' . implode(', ', $columns);
    }

    /**
     * @param array|null $tables
     * @param array $params the binding parameters to be populated.
     *
     * @psalm-param array<array-key, array|Query|string> $tables
     *
     * @throws Exception|InvalidConfigException|NotSupportedException
     *
     * @return string the FROM clause built from {@see Query::$from}.
     */
    public function buildFrom(?array $tables, array &$params): string
    {
        if (empty($tables)) {
            return '';
        }

        $tables = $this->quoteTableNames($tables, $params);

        return 'FROM ' . implode(', ', $tables);
    }

    /**
     * @param array $joins
     * @param array $params the binding parameters to be populated.
     *
     * @psalm-param array<
     *   array-key,
     *   array{
     *     0?:string,
     *     1?:array<array-key, Query|string>|string,
     *     2?:array|ExpressionInterface|string|null
     *   }|null
     * > $joins
     *
     * @throws Exception if the $joins parameter is not in proper format.
     *
     * @return string the JOIN clause built from {@see Query::$join}.
     */
    public function buildJoin(array $joins, array &$params): string
    {
        if (empty($joins)) {
            return '';
        }

        foreach ($joins as $i => $join) {
            if (!is_array($join) || !isset($join[0], $join[1])) {
                throw new Exception(
                    'A join clause must be specified as an array of join type, join table, and optionally join '
                    . 'condition.'
                );
            }

            /* 0:join type, 1:join table, 2:on-condition (optional) */
            [$joinType, $table] = $join;

            $tables = $this->quoteTableNames((array) $table, $params);

            /** @var string $table */
            $table = reset($tables);
            $joins[$i] = "$joinType $table";

            if (isset($join[2])) {
                $condition = $this->buildCondition($join[2], $params);
                if ($condition !== '') {
                    $joins[$i] .= ' ON ' . $condition;
                }
            }
        }

        /** @psalm-var array<string> $joins */
        return implode($this->separator, $joins);
    }

    /**
     * Quotes table names passed.
     *
     * @param array $tables
     * @param array $params
     *
     * @psalm-param array<array-key, array|Query|string> $tables
     *
     * @throws Exception|InvalidConfigException|NotSupportedException
     *
     * @return array
     */
    private function quoteTableNames(array $tables, array &$params): array
    {
        foreach ($tables as $i => $table) {
            if ($table instanceof Query) {
                [$sql, $params] = $this->build($table, $params);
                $tables[$i] = "($sql) " . $this->db->quoteTableName((string) $i);
            } elseif (is_string($table) && is_string($i)) {
                if (strpos($table, '(') === false) {
                    $table = $this->db->quoteTableName($table);
                }
                $tables[$i] = "$table " . $this->db->quoteTableName($i);
            } elseif (is_string($table) && strpos($table, '(') === false) {
                $tableWithAlias = $this->extractAlias($table);
                if (is_array($tableWithAlias)) { // with alias
                    $tables[$i] = $this->db->quoteTableName($tableWithAlias[1]) . ' '
                        . $this->db->quoteTableName($tableWithAlias[2]);
                } else {
                    $tables[$i] = $this->db->quoteTableName($table);
                }
            }
        }

        return $tables;
    }

    /**
     * @param array|string $condition
     * @param array $params the binding parameters to be populated.
     *
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException
     *
     * @return string the WHERE clause built from {@see Query::$where}.
     */
    public function buildWhere($condition, array &$params = []): string
    {
        $where = $this->buildCondition($condition, $params);

        return ($where === '') ? '' : ('WHERE ' . $where);
    }

    /**
     * @param array $columns
     * @psalm-param array<string, Expression|string> $columns
     *
     * @param array $params the binding parameters to be populated
     *
     * @throws Exception|InvalidArgumentException
     *
     * @return string the GROUP BY clause
     */
    public function buildGroupBy(array $columns, array &$params = []): string
    {
        if (empty($columns)) {
            return '';
        }

        foreach ($columns as $i => $column) {
            if ($column instanceof Expression) {
                $columns[$i] = $this->buildExpression($column);
                $params = array_merge($params, $column->getParams());
            } elseif (strpos($column, '(') === false) {
                $columns[$i] = $this->db->quoteColumnName($column);
            }
        }

        return 'GROUP BY ' . implode(', ', $columns);
    }

    /**
     * @param array|string $condition
     * @param array $params the binding parameters to be populated.
     *
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException
     *
     * @return string the HAVING clause built from {@see Query::$having}.
     */
    public function buildHaving($condition, array &$params = []): string
    {
        $having = $this->buildCondition($condition, $params);

        return ($having === '') ? '' : ('HAVING ' . $having);
    }

    /**
     * Builds the ORDER BY and LIMIT/OFFSET clauses and appends them to the given SQL.
     *
     * @param string $sql the existing SQL (without ORDER BY/LIMIT/OFFSET).
     * @param array $orderBy the order by columns. See {@see Query::orderBy} for more details on how to specify this
     * parameter.
     * @param Expression|int|null $limit the limit number. See {@see Query::limit} for more details.
     * @param Expression|int|null $offset the offset number. See {@see Query::offset} for more details.
     * @param array $params the binding parameters to be populated.
     *
     * @psalm-param array<string, Expression|int|string> $orderBy
     *
     * @throws Exception|InvalidArgumentException
     *
     * @return string the SQL completed with ORDER BY/LIMIT/OFFSET (if any).
     */
    public function buildOrderByAndLimit(
        string $sql,
        array $orderBy,
        $limit,
        $offset,
        array &$params = []
    ): string {
        $orderBy = $this->buildOrderBy($orderBy, $params);
        if ($orderBy !== '') {
            $sql .= $this->separator . $orderBy;
        }
        $limit = $this->buildLimit($limit, $offset);
        if ($limit !== '') {
            $sql .= $this->separator . $limit;
        }

        return $sql;
    }

    /**
     * @param array $columns
     * @param array $params the binding parameters to be populated
     *
     * @psalm-param array<string, Expression|int|string> $columns
     *
     * @throws Exception|InvalidArgumentException
     *
     * @return string the ORDER BY clause built from {@see Query::$orderBy}.
     */
    public function buildOrderBy(array $columns, array &$params = []): string
    {
        if (empty($columns)) {
            return '';
        }

        $orders = [];

        foreach ($columns as $name => $direction) {
            if ($direction instanceof Expression) {
                $orders[] = $this->buildExpression($direction);
                $params = array_merge($params, $direction->getParams());
            } else {
                $orders[] = $this->db->quoteColumnName($name) . ($direction === SORT_DESC ? ' DESC' : '');
            }
        }

        return 'ORDER BY ' . implode(', ', $orders);
    }

    /**
     * @param Expression|int|null $limit
     * @param Expression|int|null $offset
     *
     * @return string the LIMIT and OFFSET clauses.
     */
    public function buildLimit($limit, $offset): string
    {
        $sql = '';

        if ($this->hasLimit($limit)) {
            $sql = 'LIMIT ' . (string) $limit;
        }

        if ($this->hasOffset($offset)) {
            $sql .= ' OFFSET ' . (string) $offset;
        }

        return ltrim($sql);
    }

    /**
     * Checks to see if the given limit is effective.
     *
     * @param mixed $limit the given limit.
     *
     * @return bool whether the limit is effective.
     */
    protected function hasLimit($limit): bool
    {
        return ($limit instanceof ExpressionInterface) || ctype_digit((string) $limit);
    }

    /**
     * Checks to see if the given offset is effective.
     *
     * @param mixed $offset the given offset.
     *
     * @return bool whether the offset is effective.
     */
    protected function hasOffset($offset): bool
    {
        return ($offset instanceof ExpressionInterface) || (ctype_digit((string)$offset) && (string)$offset !== '0');
    }

    /**
     * @param array $unions
     * @param array $params the binding parameters to be populated
     *
     * @psalm-param array<array{query:Query|string, all:bool}> $unions
     *
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException
     *
     * @return string the UNION clause built from {@see Query::$union}.
     */
    public function buildUnion(array $unions, array &$params): string
    {
        if (empty($unions)) {
            return '';
        }

        $result = '';

        foreach ($unions as $i => $union) {
            $query = $union['query'];
            if ($query instanceof Query) {
                [$unions[$i]['query'], $params] = $this->build($query, $params);
            }

            $result .= 'UNION ' . ($union['all'] ? 'ALL ' : '') . '( ' . $unions[$i]['query'] . ' ) ';
        }

        return trim($result);
    }

    /**
     * Processes columns and properly quotes them if necessary.
     *
     * It will join all columns into a string with comma as separators.
     *
     * @param array|string $columns the columns to be processed.
     *
     * @psalm-param array<array-key, ExpressionInterface|string>|string $columns
     *
     * @throws Exception|InvalidArgumentException
     *
     * @return string the processing result.
     */
    public function buildColumns($columns): string
    {
        if (!is_array($columns)) {
            if (strpos($columns, '(') !== false) {
                return $columns;
            }

            $rawColumns = $columns;
            $columns = preg_split('/\s*,\s*/', $columns, -1, PREG_SPLIT_NO_EMPTY);

            if ($columns === false) {
                throw new InvalidArgumentException("$rawColumns is not valid columns.");
            }
        }
        foreach ($columns as $i => $column) {
            if ($column instanceof ExpressionInterface) {
                $columns[$i] = $this->buildExpression($column);
            } elseif (strpos($column, '(') === false) {
                $columns[$i] = $this->db->quoteColumnName($column);
            }
        }

        return implode(', ', $columns);
    }

    /**
     * Parses the condition specification and generates the corresponding SQL expression.
     *
     * @param array|ExpressionInterface|string|null $condition the condition specification.
     * Please refer to {@see Query::where()} on how to specify a condition.
     * @param array $params the binding parameters to be populated.
     *
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException
     *
     * @return string the generated SQL expression.
     */
    public function buildCondition($condition, array &$params = []): string
    {
        if (is_array($condition)) {
            if (empty($condition)) {
                return '';
            }

            $condition = $this->createConditionFromArray($condition);
        }

        if ($condition instanceof ExpressionInterface) {
            return $this->buildExpression($condition, $params);
        }

        return $condition ?? '';
    }

    /**
     * Transforms $condition defined in array format (as described in {@see Query::where()} to instance of
     *
     * @param array|string $condition.
     *
     * @throws InvalidArgumentException
     *
     * @return ConditionInterface
     *
     * {@see ConditionInterface|ConditionInterface} according to {@see conditionClasses} map.
     */
    public function createConditionFromArray(array $condition): ConditionInterface
    {
        /** operator format: operator, operand 1, operand 2, ... */
        if (isset($condition[0])) {
            $operator = strtoupper((string) array_shift($condition));

            $className = $this->conditionClasses[$operator] ?? SimpleCondition::class;

            /** @var ConditionInterface $className */
            return $className::fromArrayDefinition($operator, $condition);
        }

        /** hash format: 'column1' => 'value1', 'column2' => 'value2', ... */
        return new HashCondition($condition);
    }

    /**
     * Creates a SELECT EXISTS() SQL statement.
     *
     * @param string $rawSql the subquery in a raw form to select from.
     *
     * @return string the SELECT EXISTS() SQL statement.
     */
    public function selectExists(string $rawSql): string
    {
        return 'SELECT EXISTS(' . $rawSql . ')';
    }

    /**
     * Helper method to add $value to $params array using {@see PARAM_PREFIX}.
     *
     * @param mixed $value
     * @param array $params passed by reference.
     *
     * @return string the placeholder name in $params array.
     */
    public function bindParam($value, array &$params = []): string
    {
        $phName = self::PARAM_PREFIX . count($params);

        /** @psalm-var mixed */
        $params[$phName] = $value;

        return $phName;
    }

    /**
     * Extracts table alias if there is one or returns false.
     *
     * @param $table
     *
     * @return array|bool
     *
     * @psalm-return array<array-key, string>|bool
     */
    protected function extractAlias(string $table)
    {
        if (preg_match('/^(.*?)(?i:\s+as|)\s+([^ ]+)$/', $table, $matches)) {
            return $matches;
        }

        return false;
    }

    /**
     * @param array $withs
     * @param array $params
     *
     * @psalm-param array<array-key, array{query:string|Query, alias:string, recursive:bool}> $withs
     *
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException
     *
     * @return string
     */
    public function buildWithQueries(array $withs, array &$params): string
    {
        if (empty($withs)) {
            return '';
        }

        $recursive = false;
        $result = [];

        foreach ($withs as $i => $with) {
            if ($with['recursive']) {
                $recursive = true;
            }

            $query = $with['query'];
            if ($query instanceof Query) {
                [$with['query'], $params] = $this->build($query, $params);
            }

            $result[] = $with['alias'] . ' AS (' . $with['query'] . ')';
        }

        return 'WITH ' . ($recursive ? 'RECURSIVE ' : '') . implode(', ', $result);
    }

    public function getDb(): ConnectionInterface
    {
        return $this->db;
    }

    /**
     * @param string the separator between different fragments of a SQL statement.
     *
     * Defaults to an empty space. This is mainly used by {@see build()} when generating a SQL statement.
     */
    public function setSeparator(string $separator): void
    {
        $this->separator = $separator;
    }
}
