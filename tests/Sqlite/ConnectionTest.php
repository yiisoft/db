<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Sqlite;

use Yiisoft\Db\Sqlite\Tests\ConnectionTest as SqliteConnectionTest;

/**
 * @group sqlite
 */
final class ConnectionTest extends SqliteConnectionTest
{
    protected ?string $driverName = 'sqlite';
}
