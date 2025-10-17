<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema;

use Yiisoft\Db\Constraint\Check;
use Yiisoft\Db\Constraint\DefaultValue;
use Yiisoft\Db\Constraint\ForeignKey;
use Yiisoft\Db\Constraint\Index;
use Yiisoft\Db\Schema\Column\ColumnInterface;

/**
 * Represents the metadata of a database table.
 *
 * It defines a set of methods to retrieve the table name, schema name, column names, primary key, foreign keys, etc.
 *
 * The information is obtained from the database schema and may vary according to the DBMS type.
 */
interface TableSchemaInterface
{
    /**
     * Set check constraints of this table.
     *
     * @param Check ...$checks The check constraints.
     */
    public function checks(Check ...$checks): static;

    /**
     * Set metadata for a column specified.
     *
     * @param string $name The column name.
     */
    public function column(string $name, ColumnInterface $column): static;

    /**
     * Set metadata for multiple columns.
     *
     * @param ColumnInterface[] $columns The columns metadata indexed by column names.
     * @psalm-param array<string, ColumnInterface> $columns
     */
    public function columns(array $columns): static;

    /**
     * Set the comment of the table or `null` if there is no comment.
     * Not all DBMS support this.
     */
    public function comment(string|null $comment): static;

    /**
     * Set SQL for creating this table, empty string if it is not found, or `null` if it is not initialized.
     * Supported by MySQL and Oracle DBMS.
     */
    public function createSql(string|null $sql): static;

    /**
     * Set default value constraints of this table.
     *
     * @param DefaultValue ...$defaultValues The default value constraints.
     */
    public function defaultValues(DefaultValue ...$defaultValues): static;

    /**
     * Set foreign key constraints of this table.
     *
     * @param ForeignKey ...$foreignKeys The foreign key constraints.
     */
    public function foreignKeys(ForeignKey ...$foreignKeys): static;

    /**
     * @return Check[] The check constraints of this table.
     */
    public function getChecks(): array;

    /**
     * Returns the named column metadata or `null` if the named column does not exist.
     *
     * @param string $name The column name.
     */
    public function getColumn(string $name): ColumnInterface|null;

    /**
     * @return string[] The names of all columns in this table.
     */
    public function getColumnNames(): array;

    /**
     * @return ColumnInterface[] The column metadata of this table.
     * Array of {@see ColumnInterface} objects indexed by column names.
     *
     * @psalm-return array<string, ColumnInterface>
     */
    public function getColumns(): array;

    /**
     * Returns the comment of the table or `null` if no comment.
     */
    public function getComment(): string|null;

    /**
     * Returns SQL for create this table, empty string if it is not found, or `null` if it is not initialized.
     * Supported by MySQL and Oracle DBMS.
     */
    public function getCreateSql(): string|null;

    /**
     * @return DefaultValue[] The default value constraints of this table.
     */
    public function getDefaultValues(): array;

    /**
     * @return ForeignKey[] The foreign key constraints of this table.
     */
    public function getForeignKeys(): array;

    /**
     * Returns the full name of this table including the schema name.
     */
    public function getFullName(): string;

    /**
     * Returns the index constraints of this table.
     *
     * @return Index[] The index constraints of this table.
     */
    public function getIndexes(): array;

    /**
     * Returns the table name. The schema name is not included. Use {@see getFullName()} to get the table name with
     * the schema name.
     */
    public function getName(): string;

    /**
     * Returns the options of this table.
     *
     * @return string[] The options of this table.
     */
    public function getOptions(): array;

    /**
     * @return string[] The primary key column names.
     *
     * @psalm-return list<string>
     */
    public function getPrimaryKey(): array;

    /**
     * Returns the schema name that this table belongs to.
     */
    public function getSchemaName(): string;

    /**
     * Return the sequence name for the primary key or `null` if no sequence.
     */
    public function getSequenceName(): string|null;

    /**
     * Returns the unique indexes of this table.
     *
     * @return Index[] The unique indexes of this table.
     */
    public function getUniques(): array;

    /**
     * Set indexes of this table.
     *
     * @param Index ...$indexes The indexes.
     */
    public function indexes(Index ...$indexes): static;

    /**
     * Set the name of this table.
     *
     * The schema name must not be included. Use {@see schemaName()} to set the table schema name.
     */
    public function name(string $name): static;

    /**
     * Set the options of this table.
     *
     * @param string ...$options The options.
     */
    public function options(string ...$options): static;

    /**
     * Set the name of the schema that this table belongs to or empty string for the default schema.
     */
    public function schemaName(string $schemaName): static;

    /**
     * Set a sequence name for the primary key or `null` if no sequence.
     */
    public function sequenceName(string|null $sequenceName): static;
}
