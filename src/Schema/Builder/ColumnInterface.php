<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema\Builder;

/**
 * This interface defines the methods that must be implemented by classes that build the schema of a database column.
 *
 * It provides methods for setting the column name, type, length, precision, scale, default value, and other properties
 * of the column, as well as methods for adding constraints, such as a primary key, unique, and not null.
 */
interface ColumnInterface
{
    /**
     * Specify more SQL to append to column definition.
     *
     * Position modifiers will append after column definition in databases that support them.
     *
     * @param string $sql The SQL string to append.
     */
    public function append(string $sql): self;

    /**
     * Change default format string of a column.
     */
    public function setFormat(string $format): void;

    /**
     * Builds the full string for the column's schema including type, length, default value, not null and another SQL
     * fragment.
     *
     * @return string The SQL fragment to use for creating the column.
     */
    public function asString(): string;

    /**
     * Specify a `CHECK` constraint for the column.
     *
     * @param string|null $sql The SQL of the `CHECK` constraint to add.
     */
    public function check(string|null $sql): self;

    /**
     * Specifies the comment for column.
     *
     * @param string|null $comment The comment to add.
     */
    public function comment(string|null $comment): self;

    /**
     * Specify the default SQL expression for the column.
     *
     * @param string $sql The SQL expression to use as default value.
     */
    public function defaultExpression(string $sql): self;

    /**
     * Specify the default value for the column.
     *
     * @param mixed $default The default value to use.
     */
    public function defaultValue(mixed $default): self;

    /**
     * @return string|null The SQL string to append to column schema definition.
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
     * after the column type. This can be either a string, an integer or an array. If it's an array, the array values
     * will be joined into a string separated by comma.
     */
    public function getLength(): array|int|string|null;

    /**
     * @return string|null The column type definition such as `INTEGER`, `VARCHAR`, `DATETIME`, etc.
     */
    public function getType(): string|null;

    /**
     * @return bool|null Whether the column is or not nullable. If this is `true`, a `NOT NULL` constraint will be added.
     * If this is `false`, a `NULL` constraint will be added.
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
     * @see isNotNull
     */
    public function notNull(): self;

    /**
     * Adds a `NULL` constraint to the column.
     *
     * @see isNotNull
     */
    public function null(): self;

    /**
     * Adds a `UNIQUE` constraint to the column.
     *
     * @see isUnique
     */
    public function unique(): self;

    /**
     * Marks column as unsigned.
     */
    public function unsigned(): self;
}
