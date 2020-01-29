<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Mysql;

/**
 * @group db
 * @group mysql
 */
class ConnectionTest extends \Yiisoft\Db\Tests\ConnectionTest
{
    protected ?string $driverName = 'mysql';
}
