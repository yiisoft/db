<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Pgsql;

use Yiisoft\Db\Pgsql\Tests\ConnectionTest as PgsqlConnectionTest;

/**
 * @group pgsql
 */
final class ConnectionTest extends PgsqlConnectionTest
{
    protected ?string $driverName = 'pgsql';
}
