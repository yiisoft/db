<?php

declare(strict_types=1);

namespace Yiisoft\Db\Debug;

use Closure;
use Throwable;
use Yiisoft\Db\Command\CommandInterface;
use Yiisoft\Db\Query\Data\DataReaderInterface;
use Yiisoft\Db\Query\QueryInterface;
use Yiisoft\Db\Schema\Builder\ColumnInterface;

final class CommandInterfaceProxy implements CommandInterface
{
    public function __construct(
        private CommandInterface $decorated,
        private DatabaseCollector $collector
    ) {
    }

    /**
     * @psalm-suppress  MixedArgument
     */
    public function addCheck(string $table, string $name, string $expression): static
    {
        return new self($this->decorated->{__FUNCTION__}(...func_get_args()), $this->collector);
    }

    /**
     * @psalm-suppress  MixedArgument
     */
    public function addColumn(string $table, string $column, string $type): static
    {
        return new self($this->decorated->{__FUNCTION__}(...func_get_args()), $this->collector);
    }

    /**
     * @psalm-suppress  MixedArgument
     */
    public function addCommentOnColumn(string $table, string $column, string $comment): static
    {
        return new self($this->decorated->{__FUNCTION__}(...func_get_args()), $this->collector);
    }

    /**
     * @psalm-suppress  MixedArgument
     */
    public function addCommentOnTable(string $table, string $comment): static
    {
        return new self($this->decorated->{__FUNCTION__}(...func_get_args()), $this->collector);
    }

    /**
     * @psalm-suppress  MixedArgument
     */
    public function addDefaultValue(string $table, string $name, string $column, mixed $value): static
    {
        return new self($this->decorated->{__FUNCTION__}(...func_get_args()), $this->collector);
    }

    /**
     * @psalm-suppress  MixedArgument
     */
    public function addForeignKey(
        string $table,
        string $name,
        array|string $columns,
        string $referenceTable,
        array|string $referenceColumns,
        string $delete = null,
        string $update = null
    ): static {
        return new self($this->decorated->{__FUNCTION__}(...func_get_args()), $this->collector);
    }

    /**
     * @psalm-suppress  MixedArgument
     */
    public function addPrimaryKey(string $table, string $name, array|string $columns): static
    {
        return new self($this->decorated->{__FUNCTION__}(...func_get_args()), $this->collector);
    }

    /**
     * @psalm-suppress  MixedArgument
     */
    public function addUnique(string $table, string $name, array|string $columns): static
    {
        return new self($this->decorated->{__FUNCTION__}(...func_get_args()), $this->collector);
    }

    /**
     * @psalm-suppress  MixedArgument
     */
    public function alterColumn(string $table, string $column, ColumnInterface|string $type): static
    {
        return new self($this->decorated->{__FUNCTION__}(...func_get_args()), $this->collector);
    }

    /**
     * @psalm-suppress  MixedArgument
     */
    public function batchInsert(string $table, array $columns, iterable $rows): static
    {
        return new self($this->decorated->{__FUNCTION__}(...func_get_args()), $this->collector);
    }

    /**
     * @psalm-suppress  MixedArgument
     */
    public function bindParam(
        int|string $name,
        mixed &$value,
        int $dataType = null,
        int $length = null,
        mixed $driverOptions = null
    ): static {
        return new self($this->decorated->{__FUNCTION__}(...func_get_args()), $this->collector);
    }

    /**
     * @psalm-suppress  MixedArgument
     */
    public function bindValue(int|string $name, mixed $value, int $dataType = null): static
    {
        return new self($this->decorated->{__FUNCTION__}(...func_get_args()), $this->collector);
    }

    /**
     * @psalm-suppress  MixedArgument
     */
    public function bindValues(array $values): static
    {
        return new self($this->decorated->{__FUNCTION__}(...func_get_args()), $this->collector);
    }

    public function cancel(): void
    {
        $this->decorated->{__FUNCTION__}(...func_get_args());
    }

    /**
     * @psalm-suppress  MixedArgument
     */
    public function checkIntegrity(string $schema, string $table, bool $check = true): static
    {
        return new self($this->decorated->{__FUNCTION__}(...func_get_args()), $this->collector);
    }

    /**
     * @psalm-suppress  MixedArgument
     */
    public function createIndex(
        string $table,
        string $name,
        array|string $columns,
        string $indexType = null,
        string $indexMethod = null
    ): static {
        return new self($this->decorated->{__FUNCTION__}(...func_get_args()), $this->collector);
    }

    /**
     * @psalm-suppress  MixedArgument
     */
    public function createTable(string $table, array $columns, string $options = null): static
    {
        return new self($this->decorated->{__FUNCTION__}(...func_get_args()), $this->collector);
    }

    /**
     * @psalm-suppress  MixedArgument
     */
    public function createView(string $viewName, QueryInterface|string $subQuery): static
    {
        return new self($this->decorated->{__FUNCTION__}(...func_get_args()), $this->collector);
    }

    /**
     * @psalm-suppress  MixedArgument
     */
    public function delete(string $table, array|string $condition = '', array $params = []): static
    {
        return new self($this->decorated->{__FUNCTION__}(...func_get_args()), $this->collector);
    }

    /**
     * @psalm-suppress  MixedArgument
     */
    public function dropCheck(string $table, string $name): static
    {
        return new self($this->decorated->{__FUNCTION__}(...func_get_args()), $this->collector);
    }

    /**
     * @psalm-suppress  MixedArgument
     */
    public function dropColumn(string $table, string $column): static
    {
        return new self($this->decorated->{__FUNCTION__}(...func_get_args()), $this->collector);
    }

    /**
     * @psalm-suppress  MixedArgument
     */
    public function dropCommentFromColumn(string $table, string $column): static
    {
        return new self($this->decorated->{__FUNCTION__}(...func_get_args()), $this->collector);
    }

    /**
     * @psalm-suppress  MixedArgument
     */
    public function dropCommentFromTable(string $table): static
    {
        return new self($this->decorated->{__FUNCTION__}(...func_get_args()), $this->collector);
    }

    /**
     * @psalm-suppress  MixedArgument
     */
    public function dropDefaultValue(string $table, string $name): static
    {
        return new self($this->decorated->{__FUNCTION__}(...func_get_args()), $this->collector);
    }

    /**
     * @psalm-suppress  MixedArgument
     */
    public function dropForeignKey(string $table, string $name): static
    {
        return new self($this->decorated->{__FUNCTION__}(...func_get_args()), $this->collector);
    }

    /**
     * @psalm-suppress  MixedArgument
     */
    public function dropIndex(string $table, string $name): static
    {
        return new self($this->decorated->{__FUNCTION__}(...func_get_args()), $this->collector);
    }

    /**
     * @psalm-suppress  MixedArgument
     */
    public function dropPrimaryKey(string $table, string $name): static
    {
        return new self($this->decorated->{__FUNCTION__}(...func_get_args()), $this->collector);
    }

    /**
     * @psalm-suppress  MixedArgument
     */
    public function dropTable(string $table): static
    {
        return new self($this->decorated->{__FUNCTION__}(...func_get_args()), $this->collector);
    }

    /**
     * @psalm-suppress  MixedArgument
     */
    public function dropUnique(string $table, string $name): static
    {
        return new self($this->decorated->{__FUNCTION__}(...func_get_args()), $this->collector);
    }

    /**
     * @psalm-suppress  MixedArgument
     */
    public function dropView(string $viewName): static
    {
        return new self($this->decorated->{__FUNCTION__}(...func_get_args()), $this->collector);
    }

    /**
     * @psalm-suppress PossiblyUndefinedArrayOffset
     */
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

    /**
     * @psalm-suppress  MixedArgument
     */
    public function insert(string $table, QueryInterface|array $columns): static
    {
        return new self($this->decorated->{__FUNCTION__}(...func_get_args()), $this->collector);
    }

    /**
     * @psalm-suppress MixedInferredReturnType, MixedReturnStatement
     */
    public function insertWithReturningPks(string $table, array $columns): bool|array
    {
        return $this->decorated->{__FUNCTION__}(...func_get_args());
    }

    public function prepare(bool $forRead = null): void
    {
        $this->decorated->{__FUNCTION__}(...func_get_args());
    }

    /**
     * @psalm-suppress PossiblyUndefinedArrayOffset
     */
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

    /**
     * @psalm-suppress PossiblyUndefinedArrayOffset
     */
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

    /**
     * @psalm-suppress PossiblyUndefinedArrayOffset
     */
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

    /**
     * @psalm-suppress PossiblyUndefinedArrayOffset
     */
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

    /**
     * @psalm-suppress PossiblyUndefinedArrayOffset
     */
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

    /**
     * @psalm-suppress  MixedArgument
     */
    public function renameColumn(string $table, string $oldName, string $newName): static
    {
        return new self($this->decorated->{__FUNCTION__}(...func_get_args()), $this->collector);
    }

    /**
     * @psalm-suppress  MixedArgument
     */
    public function renameTable(string $table, string $newName): static
    {
        return new self($this->decorated->{__FUNCTION__}(...func_get_args()), $this->collector);
    }

    /**
     * @psalm-suppress  MixedArgument
     */
    public function resetSequence(string $table, int|string $value = null): static
    {
        return new self($this->decorated->{__FUNCTION__}(...func_get_args()), $this->collector);
    }

    /**
     * @psalm-suppress  MixedArgument
     */
    public function setRawSql(string $sql): static
    {
        return new self($this->decorated->{__FUNCTION__}(...func_get_args()), $this->collector);
    }

    /**
     * @psalm-suppress  MixedArgument
     */
    public function setRetryHandler(?Closure $handler): static
    {
        return new self($this->decorated->{__FUNCTION__}(...func_get_args()), $this->collector);
    }

    /**
     * @psalm-suppress  MixedArgument
     */
    public function setSql(string $sql): static
    {
        return new self($this->decorated->{__FUNCTION__}(...func_get_args()), $this->collector);
    }

    /**
     * @psalm-suppress  MixedArgument
     */
    public function truncateTable(string $table): static
    {
        return new self($this->decorated->{__FUNCTION__}(...func_get_args()), $this->collector);
    }

    /**
     * @psalm-suppress  MixedArgument
     */
    public function update(string $table, array $columns, array|string $condition = '', array $params = []): static
    {
        return new self($this->decorated->{__FUNCTION__}(...func_get_args()), $this->collector);
    }

    /**
     * @psalm-suppress  MixedArgument
     */
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

    public function showDatabases(): array
    {
        return $this->decorated->showDatabases();
    }
}
