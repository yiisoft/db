<?php

declare(strict_types=1);

namespace Yiisoft\Db\Query;

use Closure;
use Throwable;
use Yiisoft\Arrays\ArrayHelper;
use Yiisoft\Cache\Dependency\Dependency;
use Yiisoft\Db\Command\Command;
use Yiisoft\Db\Command\CommandInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
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
use function str_contains;
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
    protected array $select = [];
    protected ?string $selectOption = null;
    protected ?bool $distinct = null;
    protected array|null $from = null;
    protected array $groupBy = [];
    protected array|ExpressionInterface|string|null $having = null;
    protected array $join = [];
    private array $orderBy = [];
    protected array $params = [];
    protected array $union = [];
    protected array $withQueries = [];
    private bool $emulateExecution = false;
    private Closure|string|null $indexBy = null;
    private Expression|int|null $limit = null;
    private Expression|int|null $offset = null;
    private ?Dependency $queryCacheDependency = null;
    private ?int $queryCacheDuration = null;
    private QueryHelper|null $queryHelper = null;
    private array|string|ExpressionInterface|null $where = null;

    public function __construct(private ConnectionInterface $db)
    {
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

    public function addOrderBy(array|string|ExpressionInterface $columns): self
    {
        $columns = $this->createQueryHelper()->normalizeOrderBy($columns);

        if ($this->orderBy === []) {
            $this->orderBy = $columns;
        } else {
            $this->orderBy = array_merge($this->orderBy, $columns);
        }

        return $this;
    }

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

    public function andFilterHaving(array $condition): self
    {
        $condition = $this->createQueryHelper()->filterCondition($condition);

        if ($condition !== []) {
            $this->andHaving($condition);
        }

        return $this;
    }

    public function andFilterWhere(array $condition): self
    {
        $condition = $this->createQueryHelper()->filterCondition($condition);

        if ($condition !== []) {
            $this->andWhere($condition);
        }

        return $this;
    }

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

    public function addSelect(array|string|ExpressionInterface $columns): self
    {
        if ($this->select === []) {
            return $this->select($columns);
        }

        $this->select = array_merge($this->select, $this->createQueryHelper()->normalizeSelect($columns));

        return $this;
    }

    public function andFilterCompare(string $name, ?string $value, string $defaultOperator = '='): self
    {
        $operator = $defaultOperator;

        if (preg_match('/^(<>|>=|>|<=|<|=)/', (string) $value, $matches)) {
            $operator = $matches[1];
            $value = substr((string) $value, strlen($operator));
        }

        return $this->andFilterWhere([$operator, $name, $value]);
    }

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

    public function all(): array
    {
        return match ($this->emulateExecution) {
            true => [],
            false => $this->populate($this->createCommand()->queryAll()),
        };
    }

    public function average(string $q): int|float|null|string
    {
        return match ($this->emulateExecution) {
            true => null,
            false => is_numeric($avg = $this->queryScalar("AVG($q)")) ? $avg : null,
        };
    }

    public function batch(int $batchSize = 100): BatchQueryResult
    {
        return (new BatchQueryResult($this->db, $this))->batchSize($batchSize);
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
            } else {
                $results[$row[$this->indexBy]] = $value;
            }
        }

        return $results;
    }

    public function count(string $q = '*'): int|string
    {
        return match ($this->emulateExecution) {
            true => 0,
            false => is_numeric($count = $this->queryScalar("COUNT($q)")) ? $count : 0,
        };
    }

    public function createCommand(): CommandInterface
    {
        [$sql, $params] = $this->db->getQueryBuilder()->build($this);
        $command = $this->db->createCommand($sql, $params);
        $this->setCommandCache($command);

        return $command;
    }

    public function distinct(?bool $value = true): self
    {
        $this->distinct = $value;

        return $this;
    }

    public function each(int $batchSize = 100): BatchQueryResult
    {
        return (new BatchQueryResult($this->db, $this, true))->batchSize($batchSize);
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

    public function emulateExecution(bool $value = true): self
    {
        $this->emulateExecution = $value;

        return $this;
    }

    public function filterHaving(array $condition): self
    {
        $condition = $this->createQueryHelper()->filterCondition($condition);

        if ($condition !== []) {
            $this->having($condition);
        }

        return $this;
    }

    public function filterWhere(array $condition): self
    {
        $condition = $this->createQueryHelper()->filterCondition($condition);

        if ($condition !== []) {
            $this->where($condition);
        }

        return $this;
    }

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

    public function getIndexBy(): Closure|string|null
    {
        return $this->indexBy;
    }

    public function getJoin(): array
    {
        return $this->join;
    }

    public function getLimit(): Expression|int|null
    {
        return $this->limit;
    }

    public function getOffset(): Expression|int|null
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

    public function getSelect(): array
    {
        return $this->select;
    }

    public function getSelectOption(): ?string
    {
        return $this->selectOption;
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
        return empty($this->from) ? [] : $this->createQueryHelper()->cleanUpTableNames(
            $this->from,
            $this->db->getQuoter(),
        );
    }

    public function getUnion(): array
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

    public function having(array|ExpressionInterface|string|null $condition, array $params = []): self
    {
        $this->having = $condition;
        $this->addParams($params);

        return $this;
    }

    public function indexBy(Closure|string|null $column): self
    {
        $this->indexBy = $column;

        return $this;
    }

    public function innerJoin(array|string $table, array|string $on = '', array $params = []): self
    {
        $this->join[] = ['INNER JOIN', $table, $on];

        return $this->addParams($params);
    }

    public function join(string $type, array|string $table, array|string $on = '', array $params = []): self
    {
        $this->join[] = [$type, $table, $on];

        return $this->addParams($params);
    }

    public function leftJoin(array|string $table, array|string $on = '', array $params = []): self
    {
        $this->join[] = ['LEFT JOIN', $table, $on];

        return $this->addParams($params);
    }

    public function limit(Expression|int|null $limit): self
    {
        $this->limit = $limit;

        return $this;
    }

    public function max(string $q): int|float|null|string
    {
        $max = $this->queryScalar("MAX($q)");

        return is_numeric($max) ? $max : null;
    }

    public function min(string $q): int|float|null|string
    {
        $min = $this->queryScalar("MIN($q)");

        return is_numeric($min) ? $min : null;
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

    public function offset(Expression|int|null $offset): self
    {
        $this->offset = $offset;

        return $this;
    }

    public function one(): mixed
    {
        return match ($this->emulateExecution) {
            true => false,
            false => $this->createCommand()->queryOne(),
        };
    }

    public function orderBy(array|string|ExpressionInterface $columns): self
    {
        $this->orderBy = $this->createQueryHelper()->normalizeOrderBy($columns);

        return $this;
    }

    public function orFilterHaving(array $condition): self
    {
        $condition = $this->createQueryHelper()->filterCondition($condition);

        if ($condition !== []) {
            $this->orHaving($condition);
        }

        return $this;
    }

    public function orFilterWhere(array $condition): self
    {
        $condition = $this->createQueryHelper()->filterCondition($condition);

        if ($condition !== []) {
            $this->orWhere($condition);
        }

        return $this;
    }

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

    public function orWhere(array|string|ExpressionInterface $condition, array $params = []): self
    {
        if ($this->where === null) {
            $this->where = $condition;
        } else {
            $this->where = ['or', $this->where, $condition];
        }

        $this->addParams($params);

        return $this;
    }

    public function params(array $params): self
    {
        $this->params = $params;

        return $this;
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

    public function prepare(QueryBuilder $builder): self
    {
        return $this;
    }

    public function rightJoin(array|string $table, array|string $on = '', array $params = []): self
    {
        $this->join[] = ['RIGHT JOIN', $table, $on];

        return $this->addParams($params);
    }

    public function scalar(): bool|int|null|string
    {
        return match ($this->emulateExecution) {
            true => null,
            false => $this->createCommand()->queryScalar(),
        };
    }

    public function select(array|string|ExpressionInterface $columns, ?string $option = null): self
    {
        $this->select = $this->createQueryHelper()->normalizeSelect($columns);
        $this->selectOption = $option;

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

    public function shouldEmulateExecution(): bool
    {
        return $this->emulateExecution;
    }

    public function sum(string $q): int|float|null|string
    {
        return match ($this->emulateExecution) {
            true => null,
            false => is_numeric($sum = $this->queryScalar("SUM($q)")) ? $sum : null,
        };
    }

    public function union(QueryInterface|string $sql, bool $all = false): self
    {
        $this->union[] = ['query' => $sql, 'all' => $all];

        return $this;
    }

    public function where(array|string|ExpressionInterface|null $condition, array $params = []): self
    {
        $this->where = $condition;
        $this->addParams($params);

        return $this;
    }

    public function withQuery(QueryInterface|string $query, string $alias, bool $recursive = false): self
    {
        $this->withQueries[] = ['query' => $query, 'alias' => $alias, 'recursive' => $recursive];

        return $this;
    }

    public function withQueries(array $value): self
    {
        $this->withQueries = $value;

        return $this;
    }

    /**
     * Queries a scalar value by setting {@see select} first.
     *
     * Restores the value of select to make this query reusable.
     *
     * @param ExpressionInterface|string $selectExpression
     *
     *@throws Exception|InvalidConfigException|Throwable
     *
     * @return bool|int|string|null
     *
     * @psalm-suppress PossiblyUndefinedVariable
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

    private function createQueryHelper(): QueryHelper
    {
        if ($this->queryHelper === null) {
            $this->queryHelper = new QueryHelper();
        }

        return $this->queryHelper;
    }
}
