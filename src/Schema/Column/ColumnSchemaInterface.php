<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema\Column;

use Yiisoft\Db\Constant\PhpType;

/**
 * This interface defines a set of methods that must be implemented by a class that represents the column schema of a
 * database table column.
 *
 * @psalm-type ColumnInfo = array{
 *     allow_null?: bool|string|null,
 *     auto_increment?: bool|string,
 *     comment?: string|null,
 *     computed?: bool|string,
 *     db_type?: string|null,
 *     default_value?: mixed,
 *     enum_values?: array|null,
 *     extra?: string|null,
 *     primary_key?: bool|string,
 *     name?: string|null,
 *     precision?: int|string|null,
 *     scale?: int|string|null,
 *     schema?: string|null,
 *     size?: int|string|null,
 *     table?: string|null,
 *     type?: string,
 *     unsigned?: bool|string,
 *     ...<string, mixed>
 * }
 */
interface ColumnSchemaInterface
{
    /**
     * Whether to allow `null` values.
     *
     * If not set explicitly with this method call, `null` values aren't allowed.
     *
     * ```php
     * $columns = [
     *     'description' => $this->text()->allowNull(),
     * ];
     * ```
     */
    public function allowNull(bool $allowNull = true): static;

    /**
     * The database assigns auto incremented column a unique value automatically whenever you insert a new row into
     * the table. This is useful for getting unique IDs for data such as customer or employee numbers.
     * You can set the autoIncrement for `INTEGER` or `BIGINT` data types.
     *
     * If not set explicitly with this method call, the column isn't auto incremented.
     *
     * ```php
     * $columns = [
     *     'id' => $this->primaryKey()->autoIncrement(),
     * ];
     * ```
     */
    public function autoIncrement(bool $autoIncrement = true): static;

    /**
     * The comment for a column in a database table.
     *
     * The comment can give more information about the purpose or usage of the column.
     *
     * ```php
     * $columns = [
     *     'description' => $this->text()->comment('Description of the product'),
     * ];
     * ```
     */
    public function comment(string|null $comment): static;

    /**
     * A computed column is a virtual column that computes its values from an expression.
     *
     * If not set explicitly with this method call, the column isn't computed.
     *
     * ```php
     * $columns = [
     *     'description' => $this->text()->computed(true),
     * ];
     * ```
     */
    public function computed(bool $computed = true): static;

    /**
     * Sets a database data type for the column.
     *
     * The data type can be one of the built-in data types supported by the database server (such as `INTEGER`,
     * `VARCHAR`, `DATETIME`, etc.), a custom data type defined by the database server, or `null` if the database
     * allows untyped columns.
     *
     * ```php
     * $columns = [
     *     'description' => $this->text()->dbType('text'),
     * ];
     * ```
     */
    public function dbType(string|null $dbType): static;

    /**
     * Convert a value from its PHP representation to a database-specific representation.
     *
     * yiisoft/db calls it automatically by when preparing an SQL statement, so you don't usually need to call it
     * directly in your code.
     *
     * If the value is `null` or an {@see Expression}, there will be no conversion.
     */
    public function dbTypecast(mixed $value): mixed;

    /**
     * A value that's automatically assigned to a column when you insert a new row into the
     * database table. The default value can be a constant value, function, value derived from other columns,
     * non-computed column name, or their combinations.
     *
     * ```php
     * $columns = [
     *     'description' => $this->text()->defaultValue('Description of the product'),
     * ];
     * ```
     */
    public function defaultValue(mixed $defaultValue): static;

    /**
     * The list of possible values for the `ENUM` column.
     *
     * ```php
     * $columns = [
     *     'status' => $this->string(16)->enumValues(['active', 'inactive']),
     * ];
     * ```
     */
    public function enumValues(array|null $enumValues): static;

    /**
     * Extra SQL to append to the generated SQL for a column.
     *
     * This can be useful for adding custom constraints or other SQL statements that aren't supported by the column
     * schema itself.
     *
     * ```php
     * $columns = [
     *     'description' => $this->text()->extra('ON UPDATE CURRENT_TIMESTAMP'),
     * ];
     * ```
     */
    public function extra(string|null $extra): static;

    /**
     * @return string|null The comment of the column.
     *
     * @see comment()
     */
    public function getComment(): string|null;

    /**
     * @return string|null The database data type of the column.
     * Null means the column has no type in the database.
     *
     * Note that the type includes size for columns supporting it, e.g. `varchar(128)`. The size can be obtained
     * separately via {@see getSize()}.
     *
     * @see dbType()
     */
    public function getDbType(): string|null;

    /**
     * @return mixed The default value of the column.
     *
     * @see defaultValue()
     */
    public function getDefaultValue(): mixed;

    /**
     * @return array|null The enum values of the column.
     *
     * @see enumValues()
     */
    public function getEnumValues(): array|null;

    /**
     * @return string|null The extra SQL for the column.
     *
     * @see extra()
     */
    public function getExtra(): string|null;

    /**
     * @return string|null The name of the column.
     */
    public function getName(): string|null;

    /**
     * @return int|null The precision of the column.
     *
     * @see precision()
     */
    public function getPrecision(): int|null;

    /**
     * Returns the PHP type of the column. Used for generating properties of a related model class.
     *
     * @return string The PHP type of the column.
     * @psalm-return PhpType::*
     *
     * @see PhpType
     */
    public function getPhpType(): string;

    /**
     * @return int|null The scale of the column.
     *
     * @see scale()
     */
    public function getScale(): int|null;

    /**
     * @return int|null The size of the column.
     *
     * @see size()
     */
    public function getSize(): int|null;

    /**
     * @return string The type of the column.
     *
     * @see type()
     */
    public function getType(): string;

    /**
     * Whether this column is nullable.
     *
     * @see allowNull()
     */
    public function isAllowNull(): bool;

    /**
     * Whether this column is auto incremental.
     *
     * This is only meaningful when {@see type} is `smallint`, `integer` or `bigint`.
     *
     * @see autoIncrement()
     */
    public function isAutoIncrement(): bool;

    /**
     * Whether this column is computed.
     *
     * @see computed()
     */
    public function isComputed(): bool;

    /**
     * Whether this column is a part of primary key.
     *
     * @see primaryKey()
     */
    public function isPrimaryKey(): bool;

    /**
     * Whether this column is unsigned. This is only meaningful when {@see type} is `tinyint`, `smallint`, `integer`
     * or `bigint`.
     *
     * @see unsigned()
     */
    public function isUnsigned(): bool;

    /**
     * Loads the column's schema information from an array.
     *
     * @psalm-param ColumnInfo $info
     */
    public function load(array $info): static;

    /**
     * Sets a name of the column.
     *
     * ```php
     * $columns = [
     *     'id' => $this->primaryKey()->name('id'),
     * ];
     * ```
     */
    public function name(string|null $name): static;

    /**
     * Converts the input value according to {@see phpType} after retrieval from the database.
     *
     * If the value is `null` or an {@see Expression}, there is no conversion.
     */
    public function phpTypecast(mixed $value): mixed;

    /**
     * The precision is the total number of digits that represent the value.
     * This is only meaningful when {@see type} is `decimal`.
     *
     * ```php
     * $columns = [
     *     'price' => $this->decimal(10, 2)->precision(10),
     * ];
     */
    public function precision(int|null $precision): static;

    /**
     * The primary key is a column or set of columns that uniquely identifies each row in a table.
     *
     * ```php
     * $columns = [
     *     'id' => $this->primaryKey(true),
     * ];
     * ```
     */
    public function primaryKey(bool $isPrimaryKey = true): static;

    /**
     * The scale is the number of digits to the right of the decimal point and is only meaningful when {@see type} is
     * `decimal`.
     *
     * ```php
     * $columns = [
     *     'price' => $this->decimal(10, 2)->scale(2),
     * ];
     * ```
     */
    public function scale(int|null $scale): static;

    /**
     * The size refers to the number of characters or digits allowed in a column of a database table. The size is
     * typically used for character or numeric data types, such as `VARCHAR`, `INT` or DECIMAL, to specify the maximum
     * length or precision of the data in the column.
     *
     * ```php
     * $columns = [
     *     'name' => $this->string()->size(255),
     * ];
     * ```
     */
    public function size(int|null $size): static;

    /**
     * The database type of the column.
     *
     * ```php
     * $columns = [
     *     'description' => $this->text()->type('text'),
     * ];
     */
    public function type(string $type): static;

    /**
     * Whether the column type is an unsigned integer.
     * It's a data type that can only represent positive whole numbers only.
     *
     * ```php
     * $columns = [
     *     'age' => $this->integer()->unsigned(),
     * ];
     * ```
     */
    public function unsigned(bool $unsigned = true): static;
}
