<?php

declare(strict_types=1);

namespace Yiisoft\Db\Constant;

/**
 * Defines the available abstract column types.
 */
final class ColumnType
{
    /**
     * Define the abstract column type as `boolean`.
     */
    public const BOOLEAN = 'boolean';
    /**
     * Define the abstract column type as `bit`.
     */
    public const BIT = 'bit';
    /**
     * Define the abstract column type as `tinyint`.
     */
    public const TINYINT = 'tinyint';
    /**
     * Define the abstract column type as `smallint`.
     */
    public const SMALLINT = 'smallint';
    /**
     * Define the abstract column type as `integer`.
     */
    public const INTEGER = 'integer';
    /**
     * Define the abstract column type as `bigint`.
     */
    public const BIGINT = 'bigint';
    /**
     * Define the abstract column type as `float`.
     */
    public const FLOAT = 'float';
    /**
     * Define the abstract column type as `double`.
     */
    public const DOUBLE = 'double';
    /**
     * Define the abstract column type as `decimal`.
     */
    public const DECIMAL = 'decimal';
    /**
     * Define the abstract column type as `money`.
     */
    public const MONEY = 'money';
    /**
     * Define the abstract column type as `char`.
     */
    public const CHAR = 'char';
    /**
     * Define the abstract column type as `string`.
     */
    public const STRING = 'string';
    /**
     * Define the abstract column type as `text`.
     */
    public const TEXT = 'text';
    /**
     * Define the abstract column type as `binary`.
     */
    public const BINARY = 'binary';
    /**
     * Define the abstract column type as `uuid`.
     */
    public const UUID = 'uuid';
    /**
     * Define the abstract column type as `timestamp`.
     */
    public const TIMESTAMP = 'timestamp';
    /**
     * Define the abstract column type as `datetime`.
     */
    public const DATETIME = 'datetime';
    /**
     * Define the abstract column type as `datetimetz`.
     */
    public const DATETIMETZ = 'datetimetz';
    /**
     * Define the abstract column type as `time`.
     */
    public const TIME = 'time';
    /**
     * Define the abstract column type as `timetz`.
     */
    public const TIMETZ = 'timetz';
    /**
     * Define the abstract column type as `date`.
     */
    public const DATE = 'date';
    /**
     * Define the abstract column type as `array`.
     */
    public const ARRAY = 'array';
    /**
     * Define the abstract column type as `structured`.
     */
    public const STRUCTURED = 'structured';
    /**
     * Define the abstract column type as `json`.
     */
    public const JSON = 'json';
}
