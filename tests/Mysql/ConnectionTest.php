<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Mysql;

use Yiisoft\Db\Drivers\Connection;
use Yiisoft\Db\Helper\Dsn;
use Yiisoft\Db\Tests\ConnectionTest as AbstractConnectionTest;

final class ConnectionTest extends AbstractConnectionTest
{
    protected ?string $driverName = 'mysql';

    public function testDsnHelper(): void
    {
        $dsn = new Dsn('mysql', '127.0.0.1', 'yiitest', '3306');

        $connection = new Connection($this->cache, $this->logger, $this->profiler, $dsn->getDsn());

        $this->assertEquals('mysql:host=127.0.0.1;dbname=yiitest;port=3306', $connection->getDsn());
    }
}
