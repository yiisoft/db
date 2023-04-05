<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Connection;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Tests\Support\Stub\Dsn;

/**
 * @group db
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class DsnTest extends TestCase
{
    public function testConstruct(): void
    {
        $dsn = new Dsn('mysql', 'localhost', 'yiitest');

        $this->assertSame('mysql', $dsn->getDriver());
        $this->assertSame('localhost', $dsn->getHost());
        $this->assertSame('yiitest', $dsn->getDatabaseName());
        $this->assertNull($dsn->getPort());
        $this->assertSame([], $dsn->getOptions());
    }

    public function testGetDatabaseName(): void
    {
        $dsn = new Dsn('mysql', 'localhost', 'yiitest', '3306', ['charset' => 'utf8']);

        $this->assertSame('yiitest', $dsn->getDatabaseName());
    }

    public function testGetDriver(): void
    {
        $dsn = new Dsn('mysql', 'localhost', 'yiitest', '3306', ['charset' => 'utf8']);

        $this->assertSame('mysql', $dsn->getDriver());
    }

    public function testGetDsn(): void
    {
        $dsn = new Dsn('mysql', 'localhost', 'yiitest', '3306', ['charset' => 'utf8']);

        $this->assertSame('mysql:host=localhost;dbname=yiitest;port=3306;charset=utf8', $dsn->asString());
        $this->assertSame('mysql:host=localhost;dbname=yiitest;port=3306;charset=utf8', $dsn->__toString());
    }

    public function testGetDsnWithoutDatabaseName(): void
    {
        $dsn = new Dsn('mysql', 'localhost', '', '3306', ['charset' => 'utf8']);

        $this->assertSame('mysql:host=localhost;port=3306;charset=utf8', $dsn->asString());
        $this->assertSame('mysql:host=localhost;port=3306;charset=utf8', $dsn->__toString());
        $this->assertEmpty($dsn->getDatabaseName());

        $dsn = new Dsn('mysql', 'localhost', null, '3306', ['charset' => 'utf8']);

        $this->assertSame('mysql:host=localhost;port=3306;charset=utf8', $dsn->asString());
        $this->assertSame('mysql:host=localhost;port=3306;charset=utf8', $dsn->__toString());
        $this->assertNull($dsn->getDatabaseName());
    }

    public function testGetHost(): void
    {
        $dsn = new Dsn('mysql', 'localhost', 'yiitest', '3306', ['charset' => 'utf8']);

        $this->assertSame('localhost', $dsn->getHost());
    }

    public function testGetPort(): void
    {
        $dsn = new Dsn('mysql', 'localhost', 'yiitest', '3306', ['charset' => 'utf8']);

        $this->assertSame('3306', $dsn->getPort());
    }
}
