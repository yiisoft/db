<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema;

/**
 * The ColumnSchema class represents the metadata of a column in a database table. It provides information about the
 * column's name, type, size, precision, and other details.
 *
 * The ColumnSchema class is used to store and retrieve metadata about a column in a database table. It is typically
 * used in conjunction with the TableSchema class, which represents the metadata of a database table as a whole.
 *
 * Here is an example of how the ColumnSchema class might be used:
 *
 * ```php
 * use Yiisoft\Db\Schema\ColumnSchema;
 *
 * $column = new ColumnSchema();
 * $column->name('id');
 * $column->allowNull(false);
 * $column->dbType('int(11)');
 * $column->phpType('integer');
 * $column->type('integer');
 * $column->defaultValue(0);
 * $column->autoIncrement(true);
 * $column->primaryKey(true);
 * ``
 */
interface ColumnSchemaInterface
{
    /**
     * The allowNull can be set to either `true` or `false`, depending on whether or not null values should be allowed
     * in the ColumnSchema class.
     *
     * By default, the allowNull is set to `false`, so if it is not specified when defining a ColumnSchema class,
     * null values will not be allowed in the ColumnSchema class.
     *
     * ```php
     * $columns = [
     *     'description' => $this->text()->allowNull(),
     * ];
     * ```
     */
    public function allowNull(bool $value): void;

    /**
     * The autoIncrement is a column that is assigned a unique value automatically by the database management system
     * (DBMS) whenever a new row is inserted into the table. This is useful for generating unique IDs for rows in the
     * table, such as customer or employee numbers. The autoIncrement attribute can be specified for `INTEGER` or
     * `BIGINT` data types.
     *
     * By default, the autoIncrement is set to `false`, so if it is not specified when defining a ColumnSchema class,
     * the autoIncrement will not be allowed in the ColumnSchema class.
     *
     * ```php
     * $columns = [
     *    'id' => $this->primaryKey()->autoIncrement(),
     * ];
     * ```
     */
    public function autoIncrement(bool $value): void;

    /**
     * The comment refers to a string of text that can be added to a column in a database table. The comment can provide
     * additional information about the purpose or usage of the column.
     *
     * By default, the comment is set to `null`, so if it is not specified when defining a ColumnSchema class, the
     * comment will not be allowed in the ColumnSchema class.
     *
     * ```php
     * $columns = [
     *    'description' => $this->text()->comment('Description of the product'),
     * ];
     * ```
     */
    public function comment(string|null $value): void;

    /**
     * A computed column is a virtual column that computes its values from an expression. We can use a constant value,
     * function, value derived from other columns, non-computed column name, or their combinations.
     *
     * By default, the computed is set to `false`, so if it is not specified when defining a ColumnSchema class, the
     * computed will not be allowed in the ColumnSchema class.
     *
     * ```php
     * $columns = [
     *   'description' => $this->text()->computed(true),
     * ];
     * ```
     */
    public function computed(bool $value): void;

    /**
     * The dbType represents the data type of a column in a database table. This property is typically used when working
     * with the database layer, which provides a set of classes and methods for interacting with databases in a
     * consistent and abstracted way.
     *
     * The data type can be one of the built-in data types supported by the database server (such as INTEGER, VARCHAR,
     * DATETIME, etc.), or it can be a custom data type defined by the database server. The dbType property is used to
     * specify the type of data that can be stored in the column and how it should be treated by the database server
     * when performing operations on it.
     *
     * For example, if a column has a dbType of INTEGER, it means that it can only store integer values and the database
     * server will perform certain optimizations and type checking when working with the column. Similarly, if a column
     * has a dbType of VARCHAR, it means that it can store character strings of a certain length, and the database
     * server will treat the data in the column as a character string when performing operations on it.
     *
     * For default dbType is null.
     *
     * ```php
     * $columns = [
     *    'description' => $this->text()->dbType('text'),
     * ];
     * ```
     */
    public function dbType(string $value): void;

    /**
     * The dbTypecast is used to convert a value from its PHP representation to a database-specific representation.
     * It is typically used when preparing a SQL statement for execution, to ensure that the values being bound to
     * placeholders in the statement are in a format that the database can understand.
     *
     * The dbTypecast method is typically called automatically by the yiisoft/db library when preparing a SQL
     * statement for execution, so you don't usually need to call it directly in your code. However, it can be useful
     * to understand how it works if you need to customize the way that values are converted for use in a SQL statement.
     *
     * If the value is null or an {@see Expression}, it will not be converted.
     */
    public function dbTypecast(mixed $value): mixed;

    /**
     * The default value is a value that is automatically assigned to a column when a new row is inserted into the
     * database table. The default value can be a constant value, function, value derived from other columns,
     * non-computed column name, or their combinations.
     *
     * By default value is set to `null`, so if it is not specified when defining a ColumnSchema class, the default
     * value will not be allowed in the ColumnSchema class.
     *
     * ```php
     * $columns = [
     *   'description' => $this->text()->defaultValue('Description of the product'),
     * ];
     * ```
     */
    public function defaultValue(mixed $value): void;

    /**
     * The enumValues is a list of possible values for the column. It is used only for `ENUM` columns.
     *
     * By default, the enumValues is set to `null`, so if it is not specified when defining a ColumnSchema class, the
     * enumValues will not be allowed in the ColumnSchema class.
     *
     * ```php
     * $columns = [
     *  'status' => $this->string(16)->enumValues(['active', 'inactive']),
     * ];
     * ```
     */
    public function enumValues(array|null $value): void;

    /**
     * The extra column schema refers to a string attribute that can be used to specify additional SQL to be appended to
     * the generated SQL for a column. This can be useful for adding custom constraints or other SQL statements that are
     * not supported by the column schema itself.
     *
     * By default, the extra is set to `null`, so if it is not specified when defining a ColumnSchema class, the extra
     * will not be allowed in the ColumnSchema class.
     *
     * ```php
     * $columns = [
     *  'description' => $this->text()->extra('ON UPDATE CURRENT_TIMESTAMP'),
     * ];
     * ```
     */
    public function extra(string|null $value): void;

    /**
     * @see comment()
     */
    public function getComment(): string|null;

    /**
     * @see dbType()
     */
    public function getDbType(): string;

    /**
     * @see defaultValue()
     */
    public function getDefaultValue(): mixed;

    /**
     * @see enumValues()
     */
    public function getEnumValues(): array|null;

    /**
     * @see extra()
     */
    public function getExtra(): string|null;

    /**
     * @see name()
     */
    public function getName(): string;

    /**
     * @see precision()
     */
    public function getPrecision(): int|null;

    /**
     * @see phpType()
     */
    public function getPhpType(): string|null;

    /**
     * @see scale()
     */
    public function getScale(): int|null;

    /**
     * @see size()
     */
    public function getSize(): int|null;

    /**
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
     * Whether this column is auto-incremental. This is only meaningful when {@see type} is `smallint`, `integer`
     * or `bigint`.
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
     * Whether this column is a primary key.
     *
     * @see primaryKey()
     */
    public function isPrimaryKey(): bool;

    /**
     * Whether this column is unsigned. This is only meaningful when {@see type} is `smallint`, `integer`
     * or `bigint`.
     *
     * @see unsigned()
     */
    public function isUnsigned(): bool;

    /**
     * Represents the name of the column in the database. It is used to generate the SQL statement.
     *
     * For default name is empty string. Db ColumnSchema class will generate name automatically based on the column
     * name.
     *
     * ```php
     * $columns = [
     *     'description' => $this->text()->name('description'),
     * ];
     * ```
     */
    public function name(string $value): void;

    /**
     * The phpType is used to return the PHP data type that is most appropriate for representing the data stored in the
     * column. This is determined based on the data type of the column as defined in the database schema. For example,
     * if the column is defined as a varchar or text data type, the phpType() method may return string. If the column
     * is defined as an int or tinyint, the phpType() method may return integer.
     *
     * By default, the phpType is set to `null`. Db ColumnSchema class will generate phpType automatically based on the
     * column type.
     *
     * ```php
     * $columns = [
     *    'description' => $this->text()->phpType('string'),
     * ];
     * ```
     */
    public function phpType(string|null $value): void;

    /**
     * Converts the input value according to {@see phpType} after retrieval from the database.
     *
     * If the value is null or an {@see Expression}, it will not be converted.
     */
    public function phpTypecast(mixed $value): mixed;

    /**
     * The precision is the total number of digits that are used to represent the value. This is only meaningful when
     * {@see type} is `decimal`.
     *
     * By default, the precision is set to `null`, so if it is not specified when defining a ColumnSchema class, the
     * precision will not be allowed in the ColumnSchema class.
     *
     * ```php
     * $columns = [
     *    'price' => $this->decimal(10, 2)->precision(10),
     * ];
     */
    public function precision(int|null $value): void;

    /**
     * the primary key is a column or set of columns that uniquely identifies each row in a table. The primaryKey of the
     * ColumnSchema class is used to specify which column or columns should be used as the primary key for a particular
     * table.
     *
     * By default, the primaryKey is set to `false`, so if it is not specified when defining a ColumnSchema class, the
     * primaryKey will not be allowed in the ColumnSchema class.
     *
     * ```php
     * $columns = [
     *   'id' => $this->primaryKey(),
     * ];
     * ```
     */
    public function primaryKey(bool $value): void;

    /**
     * The scale is the number of digits to the right of the decimal point and is only meaningful when {@see type} is
     * `decimal`.
     *
     * By default, the scale is set to `null`, so if it is not specified when defining a ColumnSchema class, the scale
     * will not be allowed in the ColumnSchema class.
     *
     * ```php
     * $columns = [
     *   'price' => $this->decimal(10, 2)->scale(2),
     * ];
     * ```
     */
    public function scale(int|null $value): void;

    /**
     * The size refers to the number of characters or digits allowed in a column of a database table. The size is
     * typically used for character or numeric data types, such as VARCHAR or INT, to specify the maximum length or
     * precision of the data that can be stored in the column.
     *
     * By default, the size is set to `null`, so if it is not specified when defining a ColumnSchema class, the size
     * will not be allowed in the ColumnSchema class.
     *
     * ```php
     * $columns = [
     *  'name' => $this->string()->size(255),
     * ];
     * ```
     */
    public function size(int|null $value): void;

    /**
     * The type of the ColumnSchema class that is used to set the data type of a column in a database table. The data
     * type of a column specifies the kind of values that can be stored in that column, such as integers, strings,
     * dates, or floating point numbers.
     *
     * By default, the type is set to empty string. Db ColumnSchema class will generate setType automatically based
     * on the column type.
     *
     * ```php
     * $columns = [
     *  'description' => $this->text()->type('text'),
     * ];
     */
    public function type(string $value): void;

    /**
     * The unsigned is used to specify that a column in a database table should be an unsigned integer. An unsigned
     * integer is a data type that can only represent positive whole numbers, and cannot represent negative numbers
     * or decimal values.
     *
     * By default, the unsigned is set to `false`, so if it is not specified when defining a ColumnSchema class, the
     * unsigned will not be allowed in the ColumnSchema class.
     *
     * ```php
     * $columns = [
     *   'age' => $this->integer()->unsigned(),
     * ];
     * ```
     */
    public function unsigned(bool $value): void;
}
