<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema\Column;

use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Constant\PhpType;
use Yiisoft\Db\Constraint\ForeignKeyConstraint;

/**
 * This interface defines a set of methods that must be implemented by a class that represents a database table column.
 */
interface ColumnInterface
{
    /**
     * Whether to allow `null` values.
     *
     * If not set explicitly with this method call, `null` values aren't allowed.
     *
     * ```php
     * $columns = [
     *     'description' => ColumnBuilder::text()->allowNull(),
     * ];
     * ```
     *
     * @deprecated Use {@see notNull()} instead. Will be removed in version 2.0.
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
     *     'id' => ColumnBuilder::primaryKey()->autoIncrement(),
     * ];
     * ```
     */
    public function autoIncrement(bool $autoIncrement = true): static;

    /**
     * The check constraint for the column to specify an expression that must be true for each row in the table.
     *
     * ```php
     * $columns = [
     *     'age' => ColumnBuilder::integer()->check('age > 0'),
     * ];
     * ```
     */
    public function check(string|null $check): static;

    /**
     * The comment for a column in a database table.
     *
     * The comment can give more information about the purpose or usage of the column.
     *
     * ```php
     * $columns = [
     *     'description' => ColumnBuilder::text()->comment('Description of the product'),
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
     *     'full_name' => ColumnBuilder::text()->computed(true),
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
     *     'description' => ColumnBuilder::text()->dbType('text'),
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
     *     'description' => ColumnBuilder::text()->defaultValue('Description of the product'),
     * ];
     * ```
     */
    public function defaultValue(mixed $defaultValue): static;

    /**
     * The list of possible values for the `ENUM` column.
     *
     * ```php
     * $columns = [
     *     'status' => ColumnBuilder::string(16)->enumValues(['active', 'inactive']),
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
     *     'updated_at' => ColumnBuilder::integer()->extra('ON UPDATE CURRENT_TIMESTAMP'),
     * ];
     * ```
     */
    public function extra(string|null $extra): static;

    /**
     * Returns the check constraint for the column.
     *
     * @see check()
     * @psalm-mutation-free
    */
    public function getCheck(): string|null;

    /**
     * @return string|null The comment of the column.
     *
     * @see comment()
     * @psalm-mutation-free
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
     * @psalm-mutation-free
     */
    public function getDbType(): string|null;

    /**
     * @return mixed The default value of the column.
     *
     * @see defaultValue()
     * @psalm-mutation-free
     */
    public function getDefaultValue(): mixed;

    /**
     * @return array|null The enum values of the column.
     *
     * @see enumValues()
     * @psalm-mutation-free
     */
    public function getEnumValues(): array|null;

    /**
     * @return string|null The extra SQL for the column.
     *
     * @see extra()
     * @psalm-mutation-free
     */
    public function getExtra(): string|null;

    /**
     * @return string|null The name of the column.
     *
     * @psalm-mutation-free
     */
    public function getName(): string|null;

    /**
     * @return int|null The precision of the column.
     *
     * @see precision()
     *
     * @deprecated Use {@see getSize()} instead. Will be removed in version 2.0.
     * @psalm-mutation-free
     */
    public function getPrecision(): int|null;

    /**
     * Returns the PHP type of the column. Used for generating properties of a related model class.
     *
     * @return string The PHP type of the column.
     * @psalm-return PhpType::*
     * @psalm-mutation-free
     */
    public function getPhpType(): string;

    /**
     * Returns the reference to the foreign key constraint.
     *
     * @see reference()
     * @psalm-mutation-free
     */
    public function getReference(): ForeignKeyConstraint|null;

    /**
     * @return int|null The scale of the column.
     *
     * @see scale()
     * @psalm-mutation-free
     */
    public function getScale(): int|null;

    /**
     * @return int|null The size of the column.
     *
     * @see size()
     * @psalm-mutation-free
     */
    public function getSize(): int|null;

    /**
     * @return string The type of the column.
     * @psalm-return ColumnType::*
     *
     * @see type()
     * @psalm-mutation-free
     */
    public function getType(): string;

    /** @psalm-mutation-free */
    public function hasDefaultValue(): bool;

    /**
     * Whether this column is nullable.
     *
     * @see allowNull()
     *
     * @deprecated Use {@see isNotNull()} instead. Will be removed in version 2.0.
     * @psalm-mutation-free
     */
    public function isAllowNull(): bool;

    /**
     * Whether this column is auto incremental.
     *
     * This is only meaningful when {@see type} is `smallint`, `integer` or `bigint`.
     *
     * @see autoIncrement()
     * @psalm-mutation-free
     */
    public function isAutoIncrement(): bool;

    /**
     * Whether this column is computed.
     *
     * @see computed()
     * @psalm-mutation-free
     */
    public function isComputed(): bool;

    /**
     * Whether this column is not nullable.
     *
     * @see notNull()
     * @psalm-mutation-free
     */
    public function isNotNull(): bool|null;

    /**
     * Whether this column is a part of primary key.
     *
     * @see primaryKey()
     * @psalm-mutation-free
     */
    public function isPrimaryKey(): bool;

    /**
     * Whether this column has a unique index.
     *
     * @see unique()
     * @psalm-mutation-free
     */
    public function isUnique(): bool;

    /**
     * Whether this column is unsigned. This is only meaningful when {@see type} is `tinyint`, `smallint`, `integer`
     * or `bigint`.
     *
     * @see unsigned()
     * @psalm-mutation-free
     */
    public function isUnsigned(): bool;

    /**
     * Whether the column is not nullable.
     *
     * ```php
     * $columns = [
     *     'description' => ColumnBuilder::text()->notNull(),
     * ];
     * ```
     */
    public function notNull(bool $notNull = true): static;

    /**
     * Whether the column is nullable. Alias of {@see notNull(false)}.
     */
    public function null(): static;

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
     *     'price' => ColumnBuilder::decimal(10, 2)->precision(10),
     * ];
     *
     * @deprecated Use {@see size()} instead. Will be removed in version 2.0.
     */
    public function precision(int|null $precision): static;

    /**
     * The primary key is a column or set of columns that uniquely identifies each row in a table.
     *
     * ```php
     * $columns = [
     *     'id' => ColumnBuilder::primaryKey(),
     * ];
     * ```
     */
    public function primaryKey(bool $primaryKey = true): static;

    /**
     * The reference to the foreign key constraint.
     *
     * ```php
     * $reference = new ForeignKeyConstraint();
     * $reference->foreignTableName('user');
     * $reference->foreignColumnNames(['id']);
     *
     * $columns = [
     *     'user_id' => ColumnBuilder::integer()->reference($reference),
     * ];
     * ```
     */
    public function reference(ForeignKeyConstraint|null $reference): static;

    /**
     * The scale is the number of digits to the right of the decimal point and is only meaningful when {@see type} is
     * `decimal`.
     *
     * ```php
     * $columns = [
     *     'price' => ColumnBuilder::decimal(10, 2)->scale(2),
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
     *     'name' => ColumnBuilder::string()->size(255),
     * ];
     * ```
     */
    public function size(int|null $size): static;

    /**
     * The database type of the column.
     *
     * ```php
     * $columns = [
     *     'description' => ColumnBuilder::text()->type('text'),
     * ];
     *
     * @psalm-param ColumnType::* $type
     */
    public function type(string $type): static;

    /**
     * Whether the column has a unique index.
     *
     * ```php
     *  $columns = [
     *      'username' => ColumnBuilder::string()->unique(),
     *  ];
     *  ```
     */
    public function unique(bool $unique = true): static;

    /**
     * Whether the column type is an unsigned integer.
     * It's a data type that can only represent positive whole numbers only.
     *
     * ```php
     * $columns = [
     *     'age' => ColumnBuilder::integer()->unsigned(),
     * ];
     * ```
     */
    public function unsigned(bool $unsigned = true): static;

    /**
     * Returns a new instance with the specified name of the column.
     *
     * ```php
     * $columns = [
     *     'id' => ColumnBuilder::primaryKey()->withName('id'),
     * ];
     * ```
     */
    public function withName(string|null $name): static;
}
