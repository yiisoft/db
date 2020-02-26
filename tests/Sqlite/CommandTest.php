<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Sqlite;

use Yiisoft\Db\Sqlite\Tests\CommandTest as SqliteCommandTest;

/**
 * @group sqlite
 */
final class CommandTest extends SqliteCommandTest
{
    protected ?string $driverName = 'sqlite';
}
