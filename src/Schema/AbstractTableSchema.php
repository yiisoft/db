<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema;

use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Schema\Column\ColumnInterface;

use function array_keys;

/**
 * Represents the metadata of a database table.
 */
abstract class AbstractTableSchema implements TableSchemaInterface
{
    private string $schemaName = '';
    private string $name = '';
    private string|null $comment = null;
    private string|null $sequenceName = null;
    /** @psalm-var string[] */
    private array $primaryKey = [];
    /** @psalm-var array<array-key, array> */
    protected array $foreignKeys = [];
    protected string|null $createSql = null;
    private string|null $catalogName = null;
    private string|null $serverName = null;

    /**
     * @param ColumnInterface[] $columns
     *
     * @psalm-param array<string, ColumnInterface> $columns
     */
    public function __construct(
        private string $fullName = '',
        private array $columns = [],
    ) {
        $values = explode('.', $this->fullName, 2);

        if (count($values) === 2) {
            [$this->schemaName, $this->name] = $values;
        } else {
            $this->name = $this->fullName;
        }

        foreach ($columns as $columnName => $column) {
            if ($column->isPrimaryKey()) {
                $this->primaryKey[] = $columnName;
            }
        }
    }

    public function getColumn(string $name): ColumnInterface|null
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

    public function column(string $name, ColumnInterface $value): void
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
