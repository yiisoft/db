<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Mysql;

use Yiisoft\Db\Mysql\Tests\ConnectionTest as MysqlConnectionTest;

/**
 * @group mysql
 */
final class ConnectionTest extends MysqlConnectionTest
{
    protected ?string $driverName = 'mysql';
}
