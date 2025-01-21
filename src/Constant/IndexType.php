<?php

declare(strict_types=1);

namespace Yiisoft\Db\Constant;

/**
 * Defines the available index types for {@see DDLQueryBuilderInterface::createIndex()} method.
 * Use driver specific implementations for other supported types if any.
 */
final class IndexType
{
    /**
     * Define the type of the index as `UNIQUE`.
     */
    public const UNIQUE = 'UNIQUE';
}
