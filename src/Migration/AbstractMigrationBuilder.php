<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration;

use Yiisoft\Db\Schema\ColumnSchemaBuilderInterface;
use Yiisoft\Db\Schema\SchemaInterface;

/**
 * AbstractMigrationBuilder contains shortcut methods to create instances of {@see ColumnSchemaBuilderInterface}.
 *
 * These can be used in database migrations to define database schema types using a PHP interface. This is useful to
 * define a schema in a DBMS independent way so that the application may run on different DBMS the same way.
 *
 * For example, you may use the following code inside your migration files:
 *
 * ```php
 * $this->createTable(
 *     'example_table',
 *     [
 *         'id' => $this->primaryKey(),
 *         'name' => $this->string(64)->notNull(),
 *         'type' => $this->integer()->notNull()->defaultValue(10),
 *         'description' => $this->text(),
 *         'rule_name' => $this->string(64),
 *         'data' => $this->text(),
 *         'created_at' => $this->datetime()->notNull(),
 *         'updated_at' => $this->datetime(),
 *     ],
 * );
 * ```
 */
abstract class AbstractMigrationBuilder
{
    public function __construct(private SchemaInterface $schema)
    {
    }

    /**
     * Creates a bigint column.
     *
     * @param int|null $length The column size or precision definition.
     *
     * This parameter will be ignored if not supported by the DBMS.
     *
     * @return ColumnSchemaBuilderInterface The column instance which can be further customized.
     */
    public function bigInteger(int $length = null): ColumnSchemaBuilderInterface
    {
        return $this->schema->createColumnSchemaBuilder(SchemaInterface::TYPE_BIGINT, $length);
    }

    /**
     * Creates a big primary key column.
     *
     * @param int|null $length The column size or precision definition.
     *
     * This parameter will be ignored if not supported by the DBMS.
     *
     * @return ColumnSchemaBuilderInterface The column instance which can be further customized.
     */
    public function bigPrimaryKey(int $length = null): ColumnSchemaBuilderInterface
    {
        return $this->schema->createColumnSchemaBuilder(SchemaInterface::TYPE_BIGPK, $length);
    }

    /**
     * Creates a binary column.
     *
     * @param int|null $length The column size or precision definition.
     *
     * This parameter will be ignored if not supported by the DBMS.
     *
     * @return ColumnSchemaBuilderInterface The column instance which can be further customized.
     */
    public function binary(int $length = null): ColumnSchemaBuilderInterface
    {
        return $this->schema->createColumnSchemaBuilder(SchemaInterface::TYPE_BINARY, $length);
    }

    /**
     * Creates a boolean column.
     *
     * @return ColumnSchemaBuilderInterface The column instance which can be further customized.
     */
    public function boolean(): ColumnSchemaBuilderInterface
    {
        return $this->schema->createColumnSchemaBuilder(SchemaInterface::TYPE_BOOLEAN);
    }

    /**
     * Creates a char column.
     *
     * @param int|null $length the column size definition i.e. the maximum string length.
     *
     * This parameter will be ignored if not supported by the DBMS.
     *
     * @return ColumnSchemaBuilderInterface The column instance which can be further customized.
     */
    public function char(int $length = null): ColumnSchemaBuilderInterface
    {
        return $this->schema->createColumnSchemaBuilder(SchemaInterface::TYPE_CHAR, $length);
    }

    /**
     * Creates a date column.
     *
     * @return ColumnSchemaBuilderInterface The column instance which can be further customized.
     */
    public function date(): ColumnSchemaBuilderInterface
    {
        return $this->schema->createColumnSchemaBuilder(SchemaInterface::TYPE_DATE);
    }

    /**
     * Creates a datetime column.
     *
     * @param int|null $precision The column value precision. First parameter passed to the column type, e.g.
     * DATETIME(precision).
     *
     * This parameter will be ignored if not supported by the DBMS.
     *
     * @return ColumnSchemaBuilderInterface The column instance which can be further customized.
     */
    public function dateTime(int $precision = null): ColumnSchemaBuilderInterface
    {
        return $this->schema->createColumnSchemaBuilder(SchemaInterface::TYPE_DATETIME, $precision);
    }

    /**
     * Creates a decimal column.
     *
     * @param int|null $precision The column value precision, which is usually the total number of digits.
     * First parameter passed to the column type, e.g. DECIMAL(precision, scale).
     *
     * This parameter will be ignored if not supported by the DBMS.
     * @param int|null $scale The column value scale, which is usually the number of digits after the decimal point.
     * Second parameter passed to the column type, e.g. DECIMAL(precision, scale).
     *
     * This parameter will be ignored if not supported by the DBMS.
     *
     * @return ColumnSchemaBuilderInterface The column instance which can be further customized.
     */
    public function decimal(int $precision = null, int $scale = null): ColumnSchemaBuilderInterface
    {
        $length = [];

        if ($precision !== null) {
            $length[] = $precision;
        }

        if ($scale !== null) {
            $length[] = $scale;
        }

        return $this->schema->createColumnSchemaBuilder(SchemaInterface::TYPE_DECIMAL, $length);
    }

    /**
     * Creates a double column.
     *
     * @param int|null $precision The column value precision. First parameter passed to the column type, e.g.
     * DOUBLE(precision).
     *
     * This parameter will be ignored if not supported by the DBMS.
     *
     * @return ColumnSchemaBuilderInterface The column instance which can be further customized.
     */
    public function double(int $precision = null): ColumnSchemaBuilderInterface
    {
        return $this->schema->createColumnSchemaBuilder(SchemaInterface::TYPE_DOUBLE, $precision);
    }

    /**
     * Creates a float column.
     *
     * @param int|null $precision The column value precision. First parameter passed to the column type, e.g.
     * FLOAT(precision).
     *
     * This parameter will be ignored if not supported by the DBMS.
     *
     * @return ColumnSchemaBuilderInterface The column instance which can be further customized.
     */
    public function float(int $precision = null): ColumnSchemaBuilderInterface
    {
        return $this->schema->createColumnSchemaBuilder(SchemaInterface::TYPE_FLOAT, $precision);
    }

    /**
     * Creates an integer column.
     *
     * @param int|null $length The column size or precision definition.
     *
     * This parameter will be ignored if not supported by the DBMS.
     *
     * @return ColumnSchemaBuilderInterface The column instance which can be further customized.
     */
    public function integer(int $length = null): ColumnSchemaBuilderInterface
    {
        return $this->schema->createColumnSchemaBuilder(SchemaInterface::TYPE_INTEGER, $length);
    }

    /**
     * Creates a JSON column.
     *
     * @return ColumnSchemaBuilderInterface The column instance which can be further customized.
     */
    public function json(): ColumnSchemaBuilderInterface
    {
        return $this->schema->createColumnSchemaBuilder(SchemaInterface::TYPE_JSON);
    }

    /**
     * Creates a money column.
     *
     * @param int|null $precision The column value precision, which is usually the total number of digits. First
     * parameter passed to the column type, e.g. DECIMAL(precision, scale).
     *
     * This parameter will be ignored if not supported by the DBMS.
     * @param int|null $scale The column value scale, which is usually the number of digits after the decimal point.
     * Second parameter passed to the column type, e.g. DECIMAL(precision, scale).
     *
     * This parameter will be ignored if not supported by the DBMS.
     *
     * @return ColumnSchemaBuilderInterface The column instance which can be further customized.
     */
    public function money(int $precision = null, int $scale = null): ColumnSchemaBuilderInterface
    {
        $length = [];

        if ($precision !== null) {
            $length[] = $precision;
        }

        if ($scale !== null) {
            $length[] = $scale;
        }

        return $this->schema->createColumnSchemaBuilder(SchemaInterface::TYPE_MONEY, $length);
    }

    /**
     * Creates a primary key column.
     *
     * @param int|null $length The column size or precision definition.
     *
     * This parameter will be ignored if not supported by the DBMS.
     *
     * @return ColumnSchemaBuilderInterface The column instance which can be further customized.
     */
    public function primaryKey(int $length = null): ColumnSchemaBuilderInterface
    {
        return $this->schema->createColumnSchemaBuilder(SchemaInterface::TYPE_PK, $length);
    }

    /**
     * Creates a smallint column.
     *
     * @param int|null $length The column size or precision definition.
     *
     * This parameter will be ignored if not supported by the DBMS.
     *
     * @return ColumnSchemaBuilderInterface The column instance which can be further customized.
     */
    public function smallInteger(int $length = null): ColumnSchemaBuilderInterface
    {
        return $this->schema->createColumnSchemaBuilder(SchemaInterface::TYPE_SMALLINT, $length);
    }

    /**
     * Creates a string column.
     *
     * @param int|null $length The column size definition i.e. the maximum string length.
     *
     * This parameter will be ignored if not supported by the DBMS.
     *
     * @return ColumnSchemaBuilderInterface The column instance which can be further customized.
     */
    public function string(int $length = null): ColumnSchemaBuilderInterface
    {
        return $this->schema->createColumnSchemaBuilder(SchemaInterface::TYPE_STRING, $length);
    }

    /**
     * Creates a text column.
     *
     * @return ColumnSchemaBuilderInterface The column instance which can be further customized.
     */
    public function text(): ColumnSchemaBuilderInterface
    {
        return $this->schema->createColumnSchemaBuilder(SchemaInterface::TYPE_TEXT);
    }

    /**
     * Creates a medium text column.
     *
     * @return ColumnSchemaBuilderInterface The column instance which can be further customized.
     */
    public function mediumtext(): ColumnSchemaBuilderInterface
    {
        return $this->schema->createColumnSchemaBuilder(SchemaInterface::TYPE_MEDIUMTEXT);
    }

    /**
     * Creates a long text column.
     *
     * @return ColumnSchemaBuilderInterface The column instance which can be further customized.
     */
    public function longtext(): ColumnSchemaBuilderInterface
    {
        return $this->schema->createColumnSchemaBuilder(SchemaInterface::TYPE_LONGTEXT);
    }

    /**
     * Creates a time column.
     *
     * @param int|null $precision The column value precision. First parameter passed to the column type, e.g.
     * TIME(precision).
     *
     * This parameter will be ignored if not supported by the DBMS.
     *
     * @return ColumnSchemaBuilderInterface The column instance which can be further customized.
     */
    public function time(int $precision = null): ColumnSchemaBuilderInterface
    {
        return $this->schema->createColumnSchemaBuilder(SchemaInterface::TYPE_TIME, $precision);
    }

    /**
     * Creates a timestamp column.
     *
     * @param int|null $precision The column value precision. First parameter passed to the column type, e.g.
     * TIMESTAMP(precision).
     *
     * This parameter will be ignored if not supported by the DBMS.
     *
     * @return ColumnSchemaBuilderInterface The column instance which can be further customized.
     */
    public function timestamp(int $precision = null): ColumnSchemaBuilderInterface
    {
        return $this->schema->createColumnSchemaBuilder(SchemaInterface::TYPE_TIMESTAMP, $precision);
    }

    /**
     * Creates a tinyint column. If tinyint is not supported by the DBMS, smallint will be used.
     *
     * @param int|null $length The column size or precision definition.
     *
     * This parameter will be ignored if not supported by the DBMS.
     *
     * @return ColumnSchemaBuilderInterface The column instance which can be further customized.
     */
    public function tinyInteger(int $length = null): ColumnSchemaBuilderInterface
    {
        return $this->schema->createColumnSchemaBuilder(SchemaInterface::TYPE_TINYINT, $length);
    }
}
