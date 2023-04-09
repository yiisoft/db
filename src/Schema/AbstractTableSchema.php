<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema;

use Yiisoft\Db\Exception\NotSupportedException;

use function array_keys;

/**
 * Represents the metadata of a database table.
 */
abstract class AbstractTableSchema implements TableSchemaInterface
{
    private string|null $schemaName = null;
    private string $name = '';
    private string|null $fullName = null;
    private string|null $comment = null;
    private string|null $sequenceName = null;
    /** @psalm-var string[] */
    private array $primaryKey = [];
    /** @psalm-var array<string, ColumnSchemaInterface> */
    private array $columns = [];
    /** @psalm-var array<array-key, array> */
    protected array $foreignKeys = [];
    protected string|null $createSql = null;
    private string|null $catalogName = null;
    private string|null $serverName = null;

    public function getColumn(string $name): ColumnSchemaInterface|null
    {
        return $this->columns[$name] ?? null;
    }

    public function getColumnNames(): array
    {
        return array_keys($this->columns);
    }

    public function getSchemaName(): string|null
    {
        return $this->schemaName;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getFullName(): string|null
    {
        return $this->fullName;
    }

    public function getSequenceName(): string|null
    {
        return $this->sequenceName;
    }

    public function getPrimaryKey(): array
    {
        return $this->primaryKey;
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getComment(): string|null
    {
        return $this->comment;
    }

    public function schemaName(string|null $value): void
    {
        $this->schemaName = $value;
    }

    public function name(string $value): void
    {
        $this->name = $value;
    }

    public function fullName(string|null $value): void
    {
        $this->fullName = $value;
    }

    public function comment(string|null $value): void
    {
        $this->comment = $value;
    }

    public function sequenceName(string|null $value): void
    {
        $this->sequenceName = $value;
    }

    public function primaryKey(string $value): void
    {
        $this->primaryKey[] = $value;
    }

    public function column(string $name, ColumnSchemaInterface $value): void
    {
        $this->columns[$name] = $value;
    }

    public function getCatalogName(): string|null
    {
        return $this->catalogName;
    }

    public function catalogName(string|null $value): void
    {
        $this->catalogName = $value;
    }

    public function getServerName(): string|null
    {
        return $this->serverName;
    }

    public function serverName(string|null $value): void
    {
        $this->serverName = $value;
    }

    public function getCreateSql(): string|null
    {
        return $this->createSql;
    }

    public function createSql(string $sql): void
    {
        $this->createSql = $sql;
    }

    public function getForeignKeys(): array
    {
        return $this->foreignKeys;
    }

    public function foreignKeys(array $value): void
    {
        $this->foreignKeys = $value;
    }

    public function foreignKey(string|int $id, array $to): void
    {
        $this->foreignKeys[$id] = $to;
    }

    public function compositeForeignKey(int $id, string $from, string $to): void
    {
        throw new NotSupportedException(static::class . ' does not support composite FK.');
    }
}
