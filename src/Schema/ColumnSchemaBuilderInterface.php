<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema;

/**
 * The ColumnSchemaBuilderInterface class is an interface that defines the methods that must be implemented by classes
 * that build the schema of a database column. It provides methods for setting the column name, type, length, precision,
 * scale, default value, and other properties of the column, as well as methods for adding constraints, such as primary
 * key, unique, and not null. Classes that implement this interface are used to create and modify the schema of a
 * database table in a database-agnostic way.
 */
interface ColumnSchemaBuilderInterface
{
    /**
     * Specify additional SQL to be appended to column definition.
     *
     * Position modifiers will be appended after column definition in databases that support them.
     *
     * @param string $sql The SQL string to be appended.
     *
     * @return self The column schema builder instance itself.
     */
    public function append(string $sql): self;

    /**
     * Changes default format string
     */
    public function setFormat(string $format): void;

    /**
     * Builds the full string for the column's schema including type, length, default value, not null and other SQL
     * fragment.
     *
     * @return string The SQL fragment that will be used for creating the column.
     */
    public function asString(): string;

    /**
     * Specify a `CHECK` constraint for the column.
     *
     * @param string|null $check The SQL of the `CHECK` constraint to be added.
     *
     * @return self The column schema builder instance itself.
     */
    public function check(string|null $check): self;

    /**
     * Specifies the comment for column.
     *
     * @param string|null $comment The comment to be added.
     *
     * @return self The column schema builder instance itself.
     */
    public function comment(string|null $comment): self;

    /**
     * Specify the default SQL expression for the column.
     *
     * @param string $default The SQL expression to be used as default value.
     *
     * @return self The column schema builder instance itself.
     */
    public function defaultExpression(string $default): self;

    /**
     * Specify the default value for the column.
     *
     * @param mixed $default The default value to be used.
     *
     * @return self The column schema builder instance itself.
     */
    public function defaultValue(mixed $default): self;

    /**
     * @return string|null The SQL string to be appended to column schema definition.
     */
    public function getAppend(): string|null;

    /**
     * @return array The mapping of abstract column types (keys) to type categories (values).
     */
    public function getCategoryMap(): array;

    /**
     * @return string|null The comment value of the column.
     */
    public function getComment(): string|null;

    /**
     * @return string|null The `CHECK` constraint for the column.
     */
    public function getCheck(): string|null;

    /**
     * @return mixed The default value of the column.
     */
    public function getDefault(): mixed;

    /**
     * @return array|int|string|null The column size or precision definition. This is what goes into the parenthesis
     * after the column type. This can be either a string, an integer or an array. If it is an array, the array values
     * will be joined into a string separated by comma.
     */
    public function getLength(): array|int|string|null;

    /**
     * @return string|null The column type definition such as INTEGER, VARCHAR, DATETIME, etc.
     */
    public function getType(): string|null;

    /**
     * @return bool|null Whether the column is or not nullable. If this is `true`, a `NOT NULL` constraint will be
     * added. If this is `false`, a `NULL` constraint will be added.
     */
    public function isNotNull(): bool|null;

    /**
     * @return bool Whether the column values should be unique. If this is `true`, a `UNIQUE` constraint will be added.
     */
    public function isUnique(): bool;

    /**
     * @return bool Whether the column values should be unsigned. If this is `true`, an `UNSIGNED` keyword will be
     * added.
     */
    public function isUnsigned(): bool;

    /**
     * Adds a `NOT NULL` constraint to the column.
     *
     * @return static The column schema builder instance itself.
     *
     * @see isNotNull
     */
    public function notNull(): self;

    /**
     * Adds a `NULL` constraint to the column.
     *
     * @return static The column schema builder instance itself.
     *
     * @see isNotNull
     */
    public function null(): self;

    /**
     * Adds a `UNIQUE` constraint to the column.
     *
     * @return static The column schema builder instance itself.
     *
     * @see isUnique
     */
    public function unique(): self;

    /**
     * Marks column as unsigned.
     *
     * @return self The column schema builder instance itself.
     */
    public function unsigned(): self;
}
