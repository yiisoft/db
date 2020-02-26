<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Sqlite;

use Yiisoft\Db\Sqlite\Tests\ColumnSchemaBuilderTest as SqliteColumnSchemaBuilderTest;

/**
 * @group sqlite
 */
final class ColumnSchemaBuilderTest extends SqliteColumnSchemaBuilderTest
{
    protected ?string $driverName = 'sqlite';
}
