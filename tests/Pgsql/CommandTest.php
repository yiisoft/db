<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Pgsql;

use Yiisoft\Db\Pgsql\Tests\CommandTest as PgsqlCommandTest;

/**
 * @group pgsql
 */
final class CommandTest extends PgsqlCommandTest
{
    protected ?string $driverName = 'pgsql';
}
