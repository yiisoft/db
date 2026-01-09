<?php

declare(strict_types=1);

namespace Yiisoft\Db\Constant;

use Yiisoft\Db\Schema\Column\ColumnFactoryInterface;

/**
 * Defines the possible sources of column information.
 *
 * It can be used in `ColumnFactoryInterface` instances when creating column objects.
 *
 * @see ColumnFactoryInterface
 */
final class ColumnInfoSource
{
    /**
     * @var string Used to indicate that column information is taken from the column definition.
     */
    public const DEFINITION = 'definition';

    /**
     * @var string Used to indicate that column information is taken from the database table schema.
     */
    public const TABLE_SCHEMA = 'table_schema';

    /**
     * @var string Used to indicate that column information is taken from the query result.
     */
    public const QUERY_RESULT = 'query_result';
}
