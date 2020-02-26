<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Mysql;

use Yiisoft\Db\Mysql\Tests\QueryBuilderTest as MysqlQueryBuilderTest;

/**
 * @group mysql
 */
final class QueryBuilderTest extends MysqlQueryBuilderTest
{
    protected ?string $driverName = 'mysql';
}
