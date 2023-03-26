<?php

declare(strict_types=1);

namespace Yiisoft\Db\Debug;

use Closure;
use Throwable;
use Yiisoft\Db\Command\CommandInterface;
use Yiisoft\Db\Profiler\ProfilerInterface;
use Yiisoft\Db\Query\Data\DataReaderInterface;
use Yiisoft\Db\Query\QueryInterface;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;

final class CommandInterfaceProxy implements CommandInterface
{
    public function __construct(
        private CommandInterface $decorated,
        private DatabaseCollector $collector
    ) {
    }

    public function addCheck(string $name, string $table, string $expression): static
    {
        return new self($this->decorated->{__FUNCTION__}(...func_get_args()), $this->collector);
    }

    public function addColumn(string $table, string $column, string $type): static
    {
        return new self($this->decorated->{__FUNCTION__}(...func_get_args()), $this->collector);
    }

    public function addCommentOnColumn(string $table, string $column, string $comment): static
    {
        return new self($this->decorated->{__FUNCTION__}(...func_get_args()), $this->collector);
    }

    public function addCommentOnTable(string $table, string $comment): static
    {
        return new self($this->decorated->{__FUNCTION__}(...func_get_args()), $this->collector);
    }

    public function addDefaultValue(string $name, string $table, string $column, mixed $value): static
    {
        return new self($this->decorated->{__FUNCTION__}(...func_get_args()), $this->collector);
    }

    public function addForeignKey(
        string $name,
        string $table,
        array|string $columns,
        string $refTable,
        array|string $refColumns,
        string $delete = null,
        string $update = null
    ): static {
        return new self($this->decorated->{__FUNCTION__}(...func_get_args()), $this->collector);
    }

    public function addPrimaryKey(string $name, string $table, array|string $columns): static
    {
        return new self($this->decorated->{__FUNCTION__}(...func_get_args()), $this->collector);
    }

    public function addUnique(string $name, string $table, array|string $columns): static
    {
        return new self($this->decorated->{__FUNCTION__}(...func_get_args()), $this->collector);
    }

    public function alterColumn(string $table, string $column, string $type): static
    {
        return new self($this->decorated->{__FUNCTION__}(...func_get_args()), $this->collector);
    }

    public function batchInsert(string $table, array $columns, iterable $rows): static
    {
        return new self($this->decorated->{__FUNCTION__}(...func_get_args()), $this->collector);
    }

    public function bindParam(
        int|string $name,
        mixed &$value,
        int $dataType = null,
        int $length = null,
        mixed $driverOptions = null
    ): static {
        return new self($this->decorated->{__FUNCTION__}(...func_get_args()), $this->collector);
    }

    public function bindValue(int|string $name, mixed $value, int $dataType = null): static
    {
        return new self($this->decorated->{__FUNCTION__}(...func_get_args()), $this->collector);
    }

    public function bindValues(array $values): static
    {
        return new self($this->decorated->{__FUNCTION__}(...func_get_args()), $this->collector);
    }

    public function cancel(): void
    {
        $this->decorated->{__FUNCTION__}(...func_get_args());
    }

    public function checkIntegrity(string $schema, string $table, bool $check = true): static
    {
        return new self($this->decorated->{__FUNCTION__}(...func_get_args()), $this->collector);
    }

    public function createIndex(
        string $name,
        string $table,
        array|string $columns,
        string $indexType = null,
        string $indexMethod = null
    ): static {
        return new self($this->decorated->{__FUNCTION__}(...func_get_args()), $this->collector);
    }

    public function createTable(string $table, array $columns, string $options = null): static
    {
        return new self($this->decorated->{__FUNCTION__}(...func_get_args()), $this->collector);
    }

    public function createView(string $viewName, QueryInterface|string $subQuery): static
    {
        return new self($this->decorated->{__FUNCTION__}(...func_get_args()), $this->collector);
    }

    public function delete(string $table, array|string $condition = '', array $params = []): static
    {
        return new self($this->decorated->{__FUNCTION__}(...func_get_args()), $this->collector);
    }

    public function dropCheck(string $name, string $table): static
    {
        return new self($this->decorated->{__FUNCTION__}(...func_get_args()), $this->collector);
    }

    public function dropColumn(string $table, string $column): static
    {
        return new self($this->decorated->{__FUNCTION__}(...func_get_args()), $this->collector);
    }

    public function dropCommentFromColumn(string $table, string $column): static
    {
        return new self($this->decorated->{__FUNCTION__}(...func_get_args()), $this->collector);
    }

    public function dropCommentFromTable(string $table): static
    {
        return new self($this->decorated->{__FUNCTION__}(...func_get_args()), $this->collector);
    }

    public function dropDefaultValue(string $name, string $table): static
    {
        return new self($this->decorated->{__FUNCTION__}(...func_get_args()), $this->collector);
    }

    public function dropForeignKey(string $name, string $table): static
    {
        return new self($this->decorated->{__FUNCTION__}(...func_get_args()), $this->collector);
    }

    public function dropIndex(string $name, string $table): static
    {
        return new self($this->decorated->{__FUNCTION__}(...func_get_args()), $this->collector);
    }

    public function dropPrimaryKey(string $name, string $table): static
    {
        return new self($this->decorated->{__FUNCTION__}(...func_get_args()), $this->collector);
    }

    public function dropTable(string $table): static
    {
        return new self($this->decorated->{__FUNCTION__}(...func_get_args()), $this->collector);
    }

    public function dropUnique(string $name, string $table): static
    {
        return new self($this->decorated->{__FUNCTION__}(...func_get_args()), $this->collector);
    }

    public function dropView(string $viewName): static
    {
        return new self($this->decorated->{__FUNCTION__}(...func_get_args()), $this->collector);
    }

    public function execute(): int
    {
        [$callStack] = debug_backtrace();

        $id = random_bytes(36);
        $this->collectQueryStart($id, $callStack['file'] . ':' . $callStack['line']);
        try {
            $result = $this->decorated->execute();
        } catch (Throwable $e) {
            $this->collectQueryError($id, $e);
            throw $e;
        }
        $this->collectQueryEnd($id, $result);
        return $result;
    }

    public function getParams(bool $asValues = true): array
    {
        return $this->decorated->getParams($asValues);
    }

    public function getRawSql(): string
    {
        return $this->decorated->getRawSql();
    }

    public function getSql(): string
    {
        return $this->decorated->getSql();
    }

    public function insert(string $table, QueryInterface|array $columns): static
    {
        return new self($this->decorated->{__FUNCTION__}(...func_get_args()), $this->collector);
    }

    public function insertWithReturningPks(string $table, array $columns): bool|array
    {
        return $this->decorated->{__FUNCTION__}(...func_get_args());
    }

    public function prepare(bool $forRead = null): void
    {
        $this->decorated->{__FUNCTION__}(...func_get_args());
    }

    public function query(): DataReaderInterface
    {
        [$callStack] = debug_backtrace();

        $id = random_bytes(36);
        $this->collectQueryStart($id, $callStack['file'] . ':' . $callStack['line']);
        try {
            $result = $this->decorated->query();
        } catch (Throwable $e) {
            $this->collectQueryError($id, $e);
            throw $e;
        }
        $rowsNumber = $result->count();
        $this->collectQueryEnd($id, $rowsNumber);
        return $result;
    }

    public function queryAll(): array
    {
        [$callStack] = debug_backtrace();

        $id = random_bytes(36);
        $this->collectQueryStart($id, $callStack['file'] . ':' . $callStack['line']);
        try {
            $result = $this->decorated->queryAll();
        } catch (Throwable $e) {
            $this->collectQueryError($id, $e);
            throw $e;
        }
        $this->collectQueryEnd($id, count($result));
        return $result;
    }

    public function queryBuilder(): QueryBuilderInterface
    {
        return $this->decorated->{__FUNCTION__}(...func_get_args());
    }

    public function queryColumn(): array
    {
        [$callStack] = debug_backtrace();

        $id = random_bytes(36);
        $this->collectQueryStart($id, $callStack['file'] . ':' . $callStack['line']);
        try {
            $result = $this->decorated->queryColumn();
        } catch (Throwable $e) {
            $this->collectQueryError($id, $e);
            throw $e;
        }
        $this->collectQueryEnd($id, count($result));
        return $result;
    }

    public function queryOne(): array|null
    {
        [$callStack] = debug_backtrace();

        $id = random_bytes(36);
        $this->collectQueryStart($id, $callStack['file'] . ':' . $callStack['line']);
        try {
            $result = $this->decorated->queryOne();
        } catch (Throwable $e) {
            $this->collectQueryError($id, $e);
            throw $e;
        }
        $this->collectQueryEnd($id, $result === null ? 0 : 1);
        return $result;
    }

    public function queryScalar(): bool|string|null|int|float
    {
        [$callStack] = debug_backtrace();

        $id = random_bytes(36);
        $this->collectQueryStart($id, $callStack['file'] . ':' . $callStack['line']);
        try {
            $result = $this->decorated->queryScalar();
        } catch (Throwable $e) {
            $this->collectQueryError($id, $e);
            throw $e;
        }
        $this->collectQueryEnd($id, $result === null ? 0 : 1);
        return $result;
    }

    public function renameColumn(string $table, string $oldName, string $newName): static
    {
        return new self($this->decorated->{__FUNCTION__}(...func_get_args()), $this->collector);
    }

    public function renameTable(string $table, string $newName): static
    {
        return new self($this->decorated->{__FUNCTION__}(...func_get_args()), $this->collector);
    }

    public function resetSequence(string $table, int|string $value = null): static
    {
        return new self($this->decorated->{__FUNCTION__}(...func_get_args()), $this->collector);
    }

    public function setProfiler(?ProfilerInterface $profiler): void
    {
        $this->decorated->{__FUNCTION__}(...func_get_args());
    }

    public function setRawSql(string $sql): static
    {
        return new self($this->decorated->{__FUNCTION__}(...func_get_args()), $this->collector);
    }

    public function setRetryHandler(?Closure $handler): static
    {
        return new self($this->decorated->{__FUNCTION__}(...func_get_args()), $this->collector);
    }

    public function setSql(string $sql): static
    {
        return new self($this->decorated->{__FUNCTION__}(...func_get_args()), $this->collector);
    }

    public function truncateTable(string $table): static
    {
        return new self($this->decorated->{__FUNCTION__}(...func_get_args()), $this->collector);
    }

    public function update(string $table, array $columns, array|string $condition = '', array $params = []): static
    {
        return new self($this->decorated->{__FUNCTION__}(...func_get_args()), $this->collector);
    }

    public function upsert(
        string $table,
        QueryInterface|array $insertColumns,
        bool|array $updateColumns = true,
        array $params = []
    ): static {
        return new self($this->decorated->{__FUNCTION__}(...func_get_args()), $this->collector);
    }

    private function collectQueryStart(string $id, string $line): void
    {
        $this->collector->collectQueryStart(
            id: $id,
            sql: $this->decorated->getSql(),
            rawSql: $this->decorated->getRawSql(),
            params: $this->decorated->getParams(),
            line: $line,
        );
    }

    private function collectQueryError(string $id, Throwable $exception): void
    {
        $this->collector->collectQueryError($id, $exception);
    }

    private function collectQueryEnd(string $id, int $rowsNumber): void
    {
        $this->collector->collectQueryEnd($id, $rowsNumber);
    }
}
