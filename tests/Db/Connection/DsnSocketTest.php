<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Connection;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Tests\Support\Stub\DsnSocket;

/**
 * @group db
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class DsnSocketTest extends TestCase
{
    public function testConstruct(): void
    {
        $dsn = new DsnSocket('mysql', '/var/run/mysqld/mysqld.sock', 'yiitest', ['charset' => 'utf8']);

        $this->assertSame('mysql', $dsn->getDriver());
        $this->assertSame('/var/run/mysqld/mysqld.sock', $dsn->getUnixSocket());
        $this->assertSame('yiitest', $dsn->getDatabaseName());
        $this->assertSame(['charset' => 'utf8'], $dsn->getOptions());
    }

    public function testGetDatabaseName(): void
    {
        $dsn = new DsnSocket('mysql', '/var/run/mysqld/mysqld.sock', 'yiitest', ['charset' => 'utf8']);

        $this->assertSame('yiitest', $dsn->getDatabaseName());
    }

    public function testGetDriver(): void
    {
        $dsn = new DsnSocket('mysql', '/var/run/mysqld/mysqld.sock', 'yiitest', ['charset' => 'utf8']);

        $this->assertSame('mysql', $dsn->getDriver());
    }

    public function testGetDsn(): void
    {
        $dsn = new DsnSocket('mysql', '/var/run/mysqld/mysqld.sock', 'yiitest', ['charset' => 'utf8']);

        $this->assertSame(
            'mysql:unix_socket=/var/run/mysqld/mysqld.sock;dbname=yiitest;charset=utf8',
            $dsn->asString(),
        );
        $this->assertSame(
            'mysql:unix_socket=/var/run/mysqld/mysqld.sock;dbname=yiitest;charset=utf8',
            $dsn->__toString(),
        );
    }
}
