<?php

declare(strict_types=1);

namespace Yiisoft\Db\Query;

use Closure;
use LogicException;
use Throwable;
use Yiisoft\Db\Command\CommandInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Constant\GettypeResult;
use Yiisoft\Db\Exception\Exception;
use InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Helper\DbArrayHelper;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;

use function array_column;
use function array_combine;
use function array_key_exists;
use function array_map;
use function array_merge;
use function array_shift;
use function array_unshift;
use function count;
use function current;
use function gettype;
use function is_array;
use function is_int;
use function is_numeric;
use function is_string;
use function key;
use function preg_match;
use function preg_split;
use function str_contains;
use function strcasecmp;
use function strlen;
use function strpos;
use function strtoupper;
use function substr;
use function trim;

/**
 * Represents a `SELECT` SQL statement in a way that's independent of DBMS.
 *
 * Provides a set of methods to ease the specification of different clauses in a `SELECT` statement.
 *
 * You can chain these methods together.
 *
 * By calling {@see createCommand()}, you can get a {@see CommandInterface} instance which can be further used to
 * perform/execute the DB query in a database.
 *
 * For example,
 *
 * ```php
 * $query = new Query;
 *
 * // compose the query
 * $query->select('id, name')->from('user')->limit(10);
 *
 * // build and execute the query
 * $rows = $query->all();
 *
 * // alternatively, you can create DB command and execute it
 * $command = $query->createCommand();
 *
 * // $command->sql returns the actual SQL
 * $rows = $command->queryAll();
 * ```
 *
 * Query internally uses the {@see \Yiisoft\Db\QueryBuilder\AbstractQueryBuilder} class to generate the SQL statement.
 *
 * @psalm-import-type SelectValue from QueryPartsInterface
 * @psalm-import-type IndexBy from QueryInterface
 * @psalm-import-type ResultCallback from QueryInterface
 */
class Query implements QueryInterface
{
    /** @psalm-var SelectValue $select */
    protected array $select = [];
    protected string|null $selectOption = null;
    protected bool $distinct = false;
    protected array $from = [];
    protected array $groupBy = [];
    protected array|ExpressionInterface|string|null $having = null;
    protected array $join = [];
    protected array $orderBy = [];
    protected array $params = [];
    /** @psalm-var ResultCallback|null $resultCallback */
    protected Closure|null $resultCallback = null;
    protected array $union = [];
    protected array $withQueries = [];
    /** @psalm-var IndexBy|null $indexBy */
    protected Closure|string|null $indexBy = null;
    protected ExpressionInterface|int|null $limit = null;
    protected ExpressionInterface|int|null $offset = null;
    protected array|string|ExpressionInterface|null $where = null;
    protected array $with = [];

    /**
     * @psalm-var list<string>
     */
    protected array $for = [];

    private bool $emulateExecution = false;
    private bool $typecasting = false;

    public function __construct(protected ConnectionInterface $db)
    {
    }

    public function addGroupBy(array|string|ExpressionInterface $columns): static
    {
        if ($columns instanceof ExpressionInterface) {
            $columns = [$columns];
        } elseif (!is_array($columns)) {
            /** @var string[] */
            $columns = preg_split('/\s*,\s*/', trim($columns), -1, PREG_SPLIT_NO_EMPTY);
        }

        if ($this->groupBy === []) {
            $this->groupBy = $columns;
        } else {
            $this->groupBy = array_merge($this->groupBy, $columns);
        }

        return $this;
    }

    public function addOrderBy(array|string|ExpressionInterface $columns): static
    {
        $columns = $this->normalizeOrderBy($columns);

        if ($this->orderBy === []) {
            $this->orderBy = $columns;
        } else {
            $this->orderBy = array_merge($this->orderBy, $columns);
        }

        return $this;
    }

    public function addParams(array $params): static
    {
        if (empty($params)) {
            return $this;
        }

        if (empty($this->params)) {
            $this->params = $params;
        } else {
            foreach ($params as $name => $value) {
                if (is_int($name)) {
                    $this->params[] = $value;
                } else {
                    $this->params[$name] = $value;
                }
            }
        }

        return $this;
    }

    public function andFilterHaving(array $condition): static
    {
        $condition = $this->filterCondition($condition);

        if ($condition !== []) {
            $this->andHaving($condition);
        }

        return $this;
    }

    public function andFilterWhere(array $condition): static
    {
        $condition = $this->filterCondition($condition);

        if ($condition !== []) {
            $this->andWhere($condition);
        }

        return $this;
    }

    public function andHaving(array|string|ExpressionInterface $condition, array $params = []): static
    {
        if ($this->having === null) {
            $this->having = $condition;
        } elseif (
            is_array($this->having)
            && isset($this->having[0])
            && strcasecmp((string) $this->having[0], 'and') === 0
        ) {
            $this->having[] = $condition;
        } else {
            $this->having = ['and', $this->having, $condition];
        }

        $this->addParams($params);

        return $this;
    }

    public function addSelect(array|bool|float|int|string|ExpressionInterface $columns): static
    {
        if ($this->select === []) {
            return $this->select($columns);
        }

        $this->select = array_merge($this->select, $this->normalizeSelect($columns));

        return $this;
    }

    public function andFilterCompare(string $column, string|null $value, string $defaultOperator = '='): static
    {
        $operator = $defaultOperator;

        if (preg_match('/^(<>|>=|>|<=|<|=)/', (string) $value, $matches)) {
            $operator = $matches[1];
            $value = substr((string) $value, strlen($operator));
        }

        return $this->andFilterWhere([$operator, $column, $value]);
    }

    public function andWhere($condition, array $params = []): static
    {
        if ($this->where === null) {
            $this->where = $condition;
        } elseif (
            is_array($this->where)
            && isset($this->where[0])
            && strcasecmp((string) $this->where[0], 'and') === 0
        ) {
            $this->where[] = $condition;
        } else {
            $this->where = ['and', $this->where, $condition];
        }

        $this->addParams($params);

        return $this;
    }

    public function all(): array
    {
        if ($this->emulateExecution === true) {
            return [];
        }

        return $this->index($this->createCommand()->queryAll());
    }

    public function average(string $sql): int|float|null|string
    {
        return match ($this->emulateExecution) {
            true => null,
            false => is_numeric($avg = $this->queryScalar("AVG($sql)")) ? $avg : null,
        };
    }

    public function batch(int $batchSize = 100): BatchQueryResultInterface
    {
        return $this->db
            ->createBatchQueryResult($this)
            ->batchSize($batchSize)
            ->resultCallback($this->index(...));
    }

    public function column(): array
    {
        if ($this->emulateExecution) {
            return [];
        }

        if ($this->indexBy === null) {
            return $this->createCommand()->queryColumn();
        }

        if (is_string($this->indexBy)) {
            if (count($this->select) === 1) {
                if (!str_contains($this->indexBy, '.') && count($tables = $this->getTablesUsedInFrom()) > 0) {
                    $this->select[] = key($tables) . '.' . $this->indexBy;
                } else {
                    $this->select[] = $this->indexBy;
                }
            }

            $rows = $this->createCommand()->queryAll();

            if (empty($rows)) {
                return [];
            }

            if (($dotPos = strpos($this->indexBy, '.')) === false) {
                $column = $this->indexBy;
            } else {
                $column = substr($this->indexBy, $dotPos + 1);
            }

            return array_column($rows, key(current($rows)), $column);
        }

        $rows = $this->createCommand()->queryAll();

        if (empty($rows)) {
            return [];
        }

        return array_combine(array_map($this->indexBy, $rows), array_column($rows, key(current($rows))));
    }

    public function count(string $sql = '*'): int|string
    {
        /** @var int|string|null $count */
        $count = $this->queryScalar("COUNT($sql)");

        if ($count === null) {
            return 0;
        }

        /** @psalm-var non-negative-int|string */
        return $count <= PHP_INT_MAX ? (int) $count : $count;
    }

    public function createCommand(): CommandInterface
    {
        [$sql, $params] = $this->db->getQueryBuilder()->build($this);
        return $this->db->createCommand($sql, $params)->withPhpTypecasting($this->typecasting);
    }

    public function distinct(bool $value = true): static
    {
        $this->distinct = $value;
        return $this;
    }

    public function each(): DataReaderInterface
    {
        return $this->createCommand()
            ->query()
            ->indexBy($this->indexBy)
            ->resultCallback($this->resultCallback !== null ? $this->callResultCallbackOnOne(...) : null);
    }

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

    public function emulateExecution(bool $value = true): static
    {
        $this->emulateExecution = $value;
        return $this;
    }

    public function filterHaving(array $condition): static
    {
        $condition = $this->filterCondition($condition);

        if ($condition !== []) {
            $this->having($condition);
        }

        return $this;
    }

    public function filterWhere(array $condition): static
    {
        $condition = $this->filterCondition($condition);

        if ($condition !== []) {
            $this->where($condition);
        }

        return $this;
    }

    public function for(string|array|null $value): static
    {
        if ($this->for !== []) {
            throw new LogicException('The `FOR` part was set earlier. Use the `setFor()` or `addFor()` method.');
        }
        return $this->setFor($value);
    }

    public function addFor(string|array|null $value): static
    {
        if ($value === null) {
            return $this;
        }

        if (is_array($value)) {
            $this->for = [...$this->for, ...$value];
            return $this;
        }

        $this->for[] = $value;
        return $this;
    }

    public function setFor(array|string|null $value): static
    {
        if ($value === null) {
            $this->for = [];
            return $this;
        }

        $this->for = (array) $value;
        return $this;
    }

    public function from(array|ExpressionInterface|string $tables): static
    {
        /**
         * @var array
         * @psalm-suppress PossiblyInvalidArgument
         */
        $this->from = match (gettype($tables)) {
            GettypeResult::ARRAY => $tables,
            GettypeResult::STRING => preg_split('/\s*,\s*/', trim($tables), -1, PREG_SPLIT_NO_EMPTY),
            default => [$tables],
        };

        return $this;
    }

    public function getDistinct(): bool
    {
        return $this->distinct;
    }

    public function getFor(): array
    {
        return $this->for;
    }

    public function getFrom(): array
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

    public function getIndexBy(): Closure|string|null
    {
        return $this->indexBy;
    }

    public function getJoins(): array
    {
        return $this->join;
    }

    public function getLimit(): ExpressionInterface|int|null
    {
        return $this->limit;
    }

    public function getOffset(): ExpressionInterface|int|null
    {
        return $this->offset;
    }

    public function getOrderBy(): array
    {
        return $this->orderBy;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    public function getResultCallback(): Closure|null
    {
        return $this->resultCallback;
    }

    public function getSelect(): array
    {
        return $this->select;
    }

    public function getSelectOption(): string|null
    {
        return $this->selectOption;
    }

    public function getTablesUsedInFrom(): array
    {
        return $this->db->getQuoter()->cleanUpTableNames($this->from);
    }

    public function getUnions(): array
    {
        return $this->union;
    }

    public function getWhere(): array|string|ExpressionInterface|null
    {
        return $this->where;
    }

    public function getWithQueries(): array
    {
        return $this->withQueries;
    }

    public function groupBy(array|string|ExpressionInterface $columns): static
    {
        /**
         * @var array
         * @psalm-suppress PossiblyInvalidArgument
         */
        $this->groupBy = match (gettype($columns)) {
            GettypeResult::ARRAY => $columns,
            GettypeResult::STRING => preg_split('/\s*,\s*/', trim($columns), -1, PREG_SPLIT_NO_EMPTY),
            default => [$columns],
        };

        return $this;
    }

    public function having(array|ExpressionInterface|string|null $condition, array $params = []): static
    {
        if ($this->having === null) {
            $this->having = $condition;
        } else {
            throw new LogicException('The `having` condition was set earlier. Use the `setHaving()`, `andHaving()` or `orHaving()` method.');
        }
        $this->addParams($params);
        return $this;
    }

    public function setHaving(array|ExpressionInterface|string|null $condition, array $params = []): static
    {
        $this->having = $condition;
        $this->addParams($params);
        return $this;
    }

    public function indexBy(Closure|string|null $column): static
    {
        $this->indexBy = $column;
        return $this;
    }

    public function innerJoin(array|string $table, array|string $on = '', array $params = []): static
    {
        $this->join[] = ['INNER JOIN', $table, $on];
        return $this->addParams($params);
    }

    public function join(string $type, array|string $table, array|string $on = '', array $params = []): static
    {
        $this->join[] = [$type, $table, $on];
        return $this->addParams($params);
    }

    public function leftJoin(array|string $table, array|string $on = '', array $params = []): static
    {
        $this->join[] = ['LEFT JOIN', $table, $on];
        return $this->addParams($params);
    }

    public function limit(ExpressionInterface|int|null $limit): static
    {
        $this->limit = $limit;
        return $this;
    }

    public function max(string $sql): int|float|null|string
    {
        $max = $this->queryScalar("MAX($sql)");
        return is_numeric($max) ? $max : null;
    }

    public function min(string $sql): int|float|null|string
    {
        $min = $this->queryScalar("MIN($sql)");
        return is_numeric($min) ? $min : null;
    }

    public function offset(ExpressionInterface|int|null $offset): static
    {
        $this->offset = $offset;
        return $this;
    }

    public function one(): array|object|null
    {
        if ($this->emulateExecution) {
            return null;
        }

        $row = $this->createCommand()->queryOne();

        if ($this->resultCallback === null || $row === null) {
            return $row;
        }

        return $this->callResultCallbackOnOne($row);
    }

    public function orderBy(array|string|ExpressionInterface $columns): static
    {
        $this->orderBy = $this->normalizeOrderBy($columns);
        return $this;
    }

    public function orFilterHaving(array $condition): static
    {
        $condition = $this->filterCondition($condition);

        if ($condition !== []) {
            $this->orHaving($condition);
        }

        return $this;
    }

    public function orFilterWhere(array $condition): static
    {
        $condition = $this->filterCondition($condition);

        if ($condition !== []) {
            $this->orWhere($condition);
        }

        return $this;
    }

    public function orHaving(array|string|ExpressionInterface $condition, array $params = []): static
    {
        if ($this->having === null) {
            $this->having = $condition;
        } else {
            $this->having = ['or', $this->having, $condition];
        }

        $this->addParams($params);

        return $this;
    }

    public function orWhere(array|string|ExpressionInterface $condition, array $params = []): static
    {
        if ($this->where === null) {
            $this->where = $condition;
        } else {
            $this->where = ['or', $this->where, $condition];
        }

        $this->addParams($params);

        return $this;
    }

    public function params(array $params): static
    {
        $this->params = $params;
        return $this;
    }

    public function prepare(QueryBuilderInterface $builder): QueryInterface
    {
        return $this;
    }

    public function resultCallback(Closure|null $resultCallback): static
    {
        $this->resultCallback = $resultCallback;
        return $this;
    }

    public function rightJoin(array|string $table, array|string $on = '', array $params = []): static
    {
        $this->join[] = ['RIGHT JOIN', $table, $on];
        return $this->addParams($params);
    }

    public function scalar(): bool|int|null|string|float
    {
        return match ($this->emulateExecution) {
            true => null,
            false => $this->createCommand()->queryScalar(),
        };
    }

    public function select(array|bool|float|int|string|ExpressionInterface $columns, ?string $option = null): static
    {
        $this->select = $this->normalizeSelect($columns);
        $this->selectOption = $option;
        return $this;
    }

    public function selectOption(string|null $value): static
    {
        $this->selectOption = $value;
        return $this;
    }

    public function setJoins(array $value): static
    {
        $this->join = $value;
        return $this;
    }

    public function setUnions(array $value): static
    {
        $this->union = $value;
        return $this;
    }

    public function shouldEmulateExecution(): bool
    {
        return $this->emulateExecution;
    }

    public function sum(string $sql): int|float|null|string
    {
        return match ($this->emulateExecution) {
            true => null,
            false => is_numeric($sum = $this->queryScalar("SUM($sql)")) ? $sum : null,
        };
    }

    public function withTypecasting(bool $typecasting = true): static
    {
        $new = clone $this;
        $new->typecasting = $typecasting;
        return $new;
    }

    public function union(QueryInterface|string $sql, bool $all = false): static
    {
        $this->union[] = ['query' => $sql, 'all' => $all];
        return $this;
    }

    public function where(array|string|ExpressionInterface|null $condition, array $params = []): static
    {
        if ($this->where === null) {
            $this->where = $condition;
        } else {
            throw new LogicException('The `where` condition was set earlier. Use the `setWhere()`, `andWhere()` or `orWhere()` method.');
        }
        $this->addParams($params);
        return $this;
    }

    public function setWhere(array|string|ExpressionInterface|null $condition, array $params = []): static
    {
        $this->where = $condition;
        $this->addParams($params);
        return $this;
    }

    public function withQuery(
        QueryInterface|string $query,
        ExpressionInterface|string $alias,
        bool $recursive = false
    ): static {
        $this->withQueries[] = ['query' => $query, 'alias' => $alias, 'recursive' => $recursive];
        return $this;
    }

    public function withQueries(array $withQueries): static
    {
        $this->withQueries = $withQueries;
        return $this;
    }

    /**
     * Queries a scalar value by setting {@see select()} first.
     *
     * Restores the value of select to make this query reusable.
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     * @throws Throwable
     */
    protected function queryScalar(string|ExpressionInterface $selectExpression): bool|int|null|string|float
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

            $command = $this->createCommand();

            $this->select = $select;
            $this->orderBy = $order;
            $this->limit = $limit;
            $this->offset = $offset;

            return $command->queryScalar();
        }

        $query = (new self($this->db))->select($selectExpression)->from(['c' => $this]);
        [$sql, $params] = $this->db->getQueryBuilder()->build($query);
        $command = $this->db->createCommand($sql, $params);

        return $command->queryScalar();
    }

    /**
     * @psalm-param list<array> $rows
     *
     * @return array[]|object[]
     *
     * @psalm-return (
     *     $rows is non-empty-list<array>
     *         ? non-empty-array<array|object>
     *         : array[]|object[]
     * )
     */
    protected function index(array $rows): array
    {
        return DbArrayHelper::index($rows, $this->indexBy, $this->resultCallback);
    }

    private function callResultCallbackOnOne(array $row): array|object
    {
        /** @psalm-var ResultCallback $this->resultCallback */
        return ($this->resultCallback)([$row])[0];
    }

    /**
     * Removes {@see Query::isEmpty()} from the given query condition.
     *
     * @param array|string $condition The original condition.
     *
     * @return array|string The condition with {@see Query::isEmpty()} removed.
     */
    private function filterCondition(array|string $condition): array|string
    {
        if (!is_array($condition)) {
            return $condition;
        }

        if (!isset($condition[0])) {
            /**
             * Hash format: 'column1' => 'value1', 'column2' => 'value2', ...
             *
             * @psalm-var mixed $value
             */
            foreach ($condition as $name => $value) {
                if ($this->isEmpty($value)) {
                    unset($condition[$name]);
                }
            }

            return $condition;
        }

        /**
         * Operator format: operator, operand 1, operand 2, ...
         *
         * @psalm-var string $operator
         */
        $operator = array_shift($condition);

        switch (strtoupper($operator)) {
            case 'NOT':
            case 'AND':
            case 'OR':
                /** @psalm-var array<array-key, array|string> $condition */
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
                } else {
                    return [];
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
     * The value is "empty" if one of the following conditions is satisfied:
     *
     * - It's `null`,
     * - an empty string (`''`),
     * - a string containing only space characters,
     * - or an empty array.
     *
     * @param mixed $value The value to check.
     *
     * @return bool If the value is empty.
     */
    private function isEmpty(mixed $value): bool
    {
        return $value === '' || $value === [] || $value === null || (is_string($value) && trim($value) === '');
    }

    /**
     * Normalizes a format of `ORDER BY` data.
     *
     * @param array|ExpressionInterface|string $columns The columns value to normalize.
     *
     * See {@see orderBy()} and {@see addOrderBy()}.
     */
    private function normalizeOrderBy(array|string|ExpressionInterface $columns): array
    {
        if ($columns instanceof ExpressionInterface) {
            return [$columns];
        }

        if (is_array($columns)) {
            return $columns;
        }

        /** @var string[] */
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
     * Normalizes the `SELECT` columns passed to {@see select()} or {@see addSelect()}.
     *
     * @psalm-param SelectValue|scalar|ExpressionInterface $columns
     * @psalm-return SelectValue
     */
    private function normalizeSelect(array|bool|float|int|string|ExpressionInterface $columns): array
    {
        /**
         * @var SelectValue
         * @psalm-suppress InvalidArgument
         */
        $columns = match (gettype($columns)) {
            GettypeResult::ARRAY => $columns,
            GettypeResult::STRING => preg_split('/\s*,\s*/', trim($columns), -1, PREG_SPLIT_NO_EMPTY),
            default => [$columns],
        };

        $select = [];

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

            // Either a string calling a function, instance of ExpressionInterface or a scalar value.
            $select[] = $columnDefinition;
        }

        return $select;
    }
}
