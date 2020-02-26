<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Mysql;

use Yiisoft\Db\Mysql\Tests\ColumnSchemaBuilderTest as MysqlColumnSchemaBuilderTest;

/**
 * @group mysql
 */
final class ColumnSchemaBuilderTest extends MysqlColumnSchemaBuilderTest
{
    protected ?string $driverName = 'mysql';
}
