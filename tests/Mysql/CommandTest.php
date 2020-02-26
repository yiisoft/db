<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Mysql;

use Yiisoft\Db\Mysql\Tests\CommandTest as MysqlCommandTest;

/**
 * @group mysql
 */
final class CommandTest extends MysqlCommandTest
{
    protected ?string $driverName = 'mysql';
}
