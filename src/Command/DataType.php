<?php

declare(strict_types=1);

namespace Yiisoft\Db\Command;

/**
 * Types of data.
 * Usually used when binding parameters.
 */
final class DataType
{
    /**
     * SQL `NULL` data type.
     */
    public const NULL = 0;

    /**
     * SQL `INTEGER` data type.
     */
    public const INTEGER = 1;

    /**
     * SQL `CHAR`, `VARCHAR`, or another string data type.
     */
    public const STRING = 2;

    /**
     * SQL large object data type.
     */
    public const LOB = 3;

    /**
     * Represents a recordset type. Not currently supported by any drivers.
     */
    public const STMT = 4;

    /**
     * Boolean data type.
     */
    public const BOOLEAN = 5;
}
