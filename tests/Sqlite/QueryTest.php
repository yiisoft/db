<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Sqlite;

use Yiisoft\Db\Sqlite\Tests\QueryTest as SqliteQueryTest;

/**
 * @group sqlite
 */
final class QueryTest extends SqliteQueryTest
{
    protected ?string $driverName = 'sqlite';
}
