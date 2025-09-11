<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema;

use Yiisoft\Db\Constraint\Check;
use Yiisoft\Db\Constraint\DefaultValue;
use Yiisoft\Db\Constraint\ForeignKey;
use Yiisoft\Db\Constraint\Index;
use Yiisoft\Db\Schema\Column\ColumnInterface;

use function array_filter;
use function array_keys;

/**
 * Represents the metadata of a database table.
 */
class TableSchema implements TableSchemaInterface
{
    /** @var Check[] */
    private array $checks = [];
    /**
     * @var ColumnInterface[]
     * @psalm-var array<string, ColumnInterface>
     */
    private array $columns = [];
    private string|null $comment = null;
    private string|null $createSql = null;
    /** @var DefaultValue[] */
    private array $defaultValues = [];
    /** @var ForeignKey[] */
    private array $foreignKeys = [];
    /** @var Index[] */
    private array $indexes = [];
    /** @var string[] */
    private array $options = [];
    private string|null $sequenceName = null;

    public function __construct(
        private string $name = '',
        private string $schemaName = '',
    ) {
    }

    public function checks(Check ...$checks): static
    {
        $this->checks = $checks;
        return $this;
    }

    public function column(string $name, ColumnInterface $column): static
    {
        $this->columns[$name] = $column;
        return $this;
    }

    public function columns(array $columns): static
    {
        $this->columns = $columns;
        return $this;
    }

    public function comment(string|null $comment): static
    {
        $this->comment = $comment;
        return $this;
    }

    public function createSql(string|null $sql): static
    {
        $this->createSql = $sql;
        return $this;
    }

    public function defaultValues(DefaultValue ...$defaultValues): static
    {
        $this->defaultValues = $defaultValues;
        return $this;
    }

    public function foreignKeys(ForeignKey ...$foreignKeys): static
    {
        $this->foreignKeys = $foreignKeys;
        return $this;
    }

    public function getChecks(): array
    {
        return $this->checks;
    }

    public function getColumn(string $name): ColumnInterface|null
    {
        return $this->columns[$name] ?? null;
    }

    public function getColumnNames(): array
    {
        return array_keys($this->columns);
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getComment(): string|null
    {
        return $this->comment;
    }

    public function getCreateSql(): string|null
    {
        return $this->createSql;
    }

    public function getDefaultValues(): array
    {
        return $this->defaultValues;
    }

    public function getForeignKeys(): array
    {
        return $this->foreignKeys;
    }

    public function getFullName(): string
    {
        if ($this->schemaName === '') {
            return $this->name;
        }

        return "$this->schemaName.$this->name";
    }

    public function getIndexes(): array
    {
        return $this->indexes;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function getPrimaryKey(): array
    {
        foreach ($this->indexes as $index) {
            if ($index->isPrimaryKey) {
                return $index->columnNames;
            }
        }

        return array_keys(
            array_filter(
                $this->columns,
                static fn (ColumnInterface $column) => $column->isPrimaryKey()
            )
        );
    }

    public function getSchemaName(): string
    {
        return $this->schemaName;
    }

    public function getSequenceName(): string|null
    {
        return $this->sequenceName;
    }

    public function getUniques(): array
    {
        return array_filter($this->indexes, static fn (Index $index): bool => $index->isUnique);
    }

    public function indexes(Index ...$indexes): static
    {
        $this->indexes = $indexes;
        return $this;
    }

    public function name(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function options(string ...$options): static
    {
        $this->options = $options;
        return $this;
    }

    public function schemaName(string $schemaName): static
    {
        $this->schemaName = $schemaName;
        return $this;
    }

    public function sequenceName(string|null $sequenceName): static
    {
        $this->sequenceName = $sequenceName;
        return $this;
    }
}
