<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Mysql;

use Yiisoft\Db\Mysql\Tests\SchemaTest as MysqlSchemaTest;

/**
 * @group mysql
 */
final class SchemaTest extends MysqlSchemaTest
{
    protected ?string $driverName = 'mysql';
}
