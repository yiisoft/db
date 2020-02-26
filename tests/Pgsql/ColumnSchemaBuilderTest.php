<?php

declare(strict_types=1);

namespace yiiunit\framework\db\pgsql;

use Yiisoft\Db\Pgsql\Tests\ColumnSchemaBuilderTest as PgsqlColumnSchemaBuilderTest;

/**
 * @group pgsql
 */
final class ColumnSchemaBuilderTest extends PgsqlColumnSchemaBuilderTest
{
    protected ?string $driverName = 'pgsql';
}
