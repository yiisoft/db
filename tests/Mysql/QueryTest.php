<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Mysql;

use Yiisoft\Db\Mysql\Tests\QueryTest as MysqlQueryTest;

/**
 * @group mysql
 */
final class QueryTest extends MysqlQueryTest
{
    protected ?string $driverName = 'mysql';
}
