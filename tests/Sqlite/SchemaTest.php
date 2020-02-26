<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Sqlite;

use Yiisoft\Db\Sqlite\Tests\SchemaTest as SqliteSchemaTest;

/**
 * @group sqlite
 */
final class SchemaTest extends SqliteSchemaTest
{
    protected ?string $driverName = 'sqlite';
}
