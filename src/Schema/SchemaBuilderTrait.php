<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema;

use Yiisoft\Db\Connection\ConnectionInterface;

/**
 * SchemaBuilderTrait contains shortcut methods to create instances of {@see ColumnSchemaBuilder}.
 *
 * These can be used in database migrations to define database schema types using a PHP interface.
 * This is useful to define a schema in a DBMS independent way so that the application may run on
 * different DBMS the same way.
 *
 * For example you may use the following code inside your migration files:
 *
 * ```php
 * $this->createTable('example_table', [
 *   'id' => $this->primaryKey(),
 *   'name' => $this->string(64)->notNull(),
 *   'type' => $this->integer()->notNull()->defaultValue(10),
 *   'description' => $this->text(),
 *   'rule_name' => $this->string(64),
 *   'data' => $this->text(),
 *   'created_at' => $this->datetime()->notNull(),
 *   'updated_at' => $this->datetime(),
 * ]);
 * ```
 */
trait SchemaBuilderTrait
{
    /**
     * @return ConnectionInterface|null the database connection to be used for schema building.
     */
    abstract protected function getDb(): ?ConnectionInterface;

    /**
     * Creates a primary key column.
     *
     * @param int|null $length column size or precision definition.
     *
     * This parameter will be ignored if not supported by the DBMS.
     *
     * @return ColumnSchemaBuilder the column instance which can be further customized.
     */
    public function primaryKey(int $length = null): ColumnSchemaBuilder
    {
        return $this->getDb()->getSchema()->createColumnSchemaBuilder(Schema::TYPE_PK, $length);
    }

    /**
     * Creates a big primary key column.
     *
     * @param int|null $length column size or precision definition.
     *
     * This parameter will be ignored if not supported by the DBMS.
     *
     * @return ColumnSchemaBuilder the column instance which can be further customized.
     */
    public function bigPrimaryKey(int $length = null): ColumnSchemaBuilder
    {
        return $this->getDb()->getSchema()->createColumnSchemaBuilder(Schema::TYPE_BIGPK, $length);
    }

    /**
     * Creates a char column.
     *
     * @param int|null $length column size definition i.e. the maximum string length.
     *
     * This parameter will be ignored if not supported by the DBMS.
     *
     * @return ColumnSchemaBuilder the column instance which can be further customized.
     */
    public function char(int $length = null): ColumnSchemaBuilder
    {
        return $this->getDb()->getSchema()->createColumnSchemaBuilder(Schema::TYPE_CHAR, $length);
    }

    /**
     * Creates a string column.
     *
     * @param int|null $length column size definition i.e. the maximum string length.
     *
     * This parameter will be ignored if not supported by the DBMS.
     *
     * @return ColumnSchemaBuilder the column instance which can be further customized.
     */
    public function string(int $length = null): ColumnSchemaBuilder
    {
        return $this->getDb()->getSchema()->createColumnSchemaBuilder(Schema::TYPE_STRING, $length);
    }

    /**
     * Creates a text column.
     *
     * @return ColumnSchemaBuilder the column instance which can be further customized.
     */
    public function text(): ColumnSchemaBuilder
    {
        return $this->getDb()->getSchema()->createColumnSchemaBuilder(Schema::TYPE_TEXT);
    }

    /**
     * Creates a tinyint column. If tinyint is not supported by the DBMS, smallint will be used.
     *
     * @param int|null $length column size or precision definition.
     *
     * This parameter will be ignored if not supported by the DBMS.
     *
     * @return ColumnSchemaBuilder the column instance which can be further customized.
     */
    public function tinyInteger(int $length = null): ColumnSchemaBuilder
    {
        return $this->getDb()->getSchema()->createColumnSchemaBuilder(Schema::TYPE_TINYINT, $length);
    }

    /**
     * Creates a smallint column.
     *
     * @param int|null $length column size or precision definition.
     *
     * This parameter will be ignored if not supported by the DBMS.
     *
     * @return ColumnSchemaBuilder the column instance which can be further customized.
     */
    public function smallInteger(int $length = null): ColumnSchemaBuilder
    {
        return $this->getDb()->getSchema()->createColumnSchemaBuilder(Schema::TYPE_SMALLINT, $length);
    }

    /**
     * Creates an integer column.
     *
     * @param int|null $length column size or precision definition.
     *
     * This parameter will be ignored if not supported by the DBMS.
     *
     * @return ColumnSchemaBuilder the column instance which can be further customized.
     */
    public function integer(int $length = null): ColumnSchemaBuilder
    {
        return $this->getDb()->getSchema()->createColumnSchemaBuilder(Schema::TYPE_INTEGER, $length);
    }

    /**
     * Creates a bigint column.
     *
     * @param int|null $length column size or precision definition.
     *
     * This parameter will be ignored if not supported by the DBMS.
     *
     * @return ColumnSchemaBuilder the column instance which can be further customized.
     */
    public function bigInteger(int $length = null): ColumnSchemaBuilder
    {
        return $this->getDb()->getSchema()->createColumnSchemaBuilder(Schema::TYPE_BIGINT, $length);
    }

    /**
     * Creates a float column.
     *
     * @param int|null $precision column value precision. First parameter passed to the column type, e.g. FLOAT(precision).
     *
     * This parameter will be ignored if not supported by the DBMS.
     *
     * @return ColumnSchemaBuilder the column instance which can be further customized.
     */
    public function float(int $precision = null): ColumnSchemaBuilder
    {
        return $this->getDb()->getSchema()->createColumnSchemaBuilder(Schema::TYPE_FLOAT, $precision);
    }

    /**
     * Creates a double column.
     *
     * @param int|null $precision column value precision. First parameter passed to the column type, e.g. DOUBLE(precision).
     *
     * This parameter will be ignored if not supported by the DBMS.
     *
     * @return ColumnSchemaBuilder the column instance which can be further customized.
     */
    public function double(int $precision = null): ColumnSchemaBuilder
    {
        return $this->getDb()->getSchema()->createColumnSchemaBuilder(Schema::TYPE_DOUBLE, $precision);
    }

    /**
     * Creates a decimal column.
     *
     * @param int|null $precision column value precision, which is usually the total number of digits.
     * First parameter passed to the column type, e.g. DECIMAL(precision, scale).
     * This parameter will be ignored if not supported by the DBMS.
     * @param int|null $scale column value scale, which is usually the number of digits after the decimal point.
     * Second parameter passed to the column type, e.g. DECIMAL(precision, scale).
     * This parameter will be ignored if not supported by the DBMS.
     *
     * @return ColumnSchemaBuilder the column instance which can be further customized.
     */
    public function decimal(int $precision = null, ?int $scale = null): ColumnSchemaBuilder
    {
        $length = [];

        if ($precision !== null) {
            $length[] = $precision;
        }

        if ($scale !== null) {
            $length[] = $scale;
        }

        return $this->getDb()->getSchema()->createColumnSchemaBuilder(Schema::TYPE_DECIMAL, $length);
    }

    /**
     * Creates a datetime column.
     *
     * @param int|null $precision column value precision. First parameter passed to the column type, e.g.
     * DATETIME(precision). This parameter will be ignored if not supported by the DBMS.
     *
     * @return ColumnSchemaBuilder the column instance which can be further customized.
     */
    public function dateTime(int $precision = null): ColumnSchemaBuilder
    {
        return $this->getDb()->getSchema()->createColumnSchemaBuilder(Schema::TYPE_DATETIME, $precision);
    }

    /**
     * Creates a timestamp column.
     *
     * @param int|null $precision column value precision. First parameter passed to the column type, e.g.
     * TIMESTAMP(precision). This parameter will be ignored if not supported by the DBMS.
     *
     * @return ColumnSchemaBuilder the column instance which can be further customized.
     */
    public function timestamp(?int $precision = null): ColumnSchemaBuilder
    {
        return $this->getDb()->getSchema()->createColumnSchemaBuilder(Schema::TYPE_TIMESTAMP, $precision);
    }

    /**
     * Creates a time column.
     *
     * @param int|null $precision column value precision. First parameter passed to the column type, e.g. TIME(precision).
     * This parameter will be ignored if not supported by the DBMS.
     *
     * @return ColumnSchemaBuilder the column instance which can be further customized.
     */
    public function time(int $precision = null): ColumnSchemaBuilder
    {
        return $this->getDb()->getSchema()->createColumnSchemaBuilder(Schema::TYPE_TIME, $precision);
    }

    /**
     * Creates a date column.
     *
     * @return ColumnSchemaBuilder the column instance which can be further customized.
     */
    public function date(): ColumnSchemaBuilder
    {
        return $this->getDb()->getSchema()->createColumnSchemaBuilder(Schema::TYPE_DATE);
    }

    /**
     * Creates a binary column.
     *
     * @param int|null $length column size or precision definition.
     * This parameter will be ignored if not supported by the DBMS.
     *
     * @return ColumnSchemaBuilder the column instance which can be further customized.
     */
    public function binary(int $length = null): ColumnSchemaBuilder
    {
        return $this->getDb()->getSchema()->createColumnSchemaBuilder(Schema::TYPE_BINARY, $length);
    }

    /**
     * Creates a boolean column.
     *
     * @return ColumnSchemaBuilder the column instance which can be further customized.
     */
    public function boolean(): ColumnSchemaBuilder
    {
        return $this->getDb()->getSchema()->createColumnSchemaBuilder(Schema::TYPE_BOOLEAN);
    }

    /**
     * Creates a money column.
     *
     * @param int|null $precision column value precision, which is usually the total number of digits.
     * First parameter passed to the column type, e.g. DECIMAL(precision, scale).
     * This parameter will be ignored if not supported by the DBMS.
     * @param int|null $scale column value scale, which is usually the number of digits after the decimal point.
     * Second parameter passed to the column type, e.g. DECIMAL(precision, scale).
     * This parameter will be ignored if not supported by the DBMS.
     *
     * @return ColumnSchemaBuilder the column instance which can be further customized.
     */
    public function money(int $precision = null, int $scale = null): ColumnSchemaBuilder
    {
        $length = [];

        if ($precision !== null) {
            $length[] = $precision;
        }

        if ($scale !== null) {
            $length[] = $scale;
        }

        return $this->getDb()->getSchema()->createColumnSchemaBuilder(Schema::TYPE_MONEY, $length);
    }

    /**
     * Creates a JSON column.
     *
     * @throws\Exceptions
     *
     * @return ColumnSchemaBuilder the column instance which can be further customized.
     */
    public function json(): ColumnSchemaBuilder
    {
        return $this->getDb()->getSchema()->createColumnSchemaBuilder(Schema::TYPE_JSON);
    }
}
