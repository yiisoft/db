<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Connection;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Connection\ConnectionProvider;
use Yiisoft\Db\Tests\Support\TestTrait;

final class ConnectionProviderTest extends TestCase
{
    use TestTrait;

    public function testConnectionProviderNonExists(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Connection with name 'default' does not exist.");

        ConnectionProvider::get();
    }

    public function testConnectionProvider(): void
    {
        $this->assertFalse(ConnectionProvider::has());
        $this->assertFalse(ConnectionProvider::has('db2'));

        $db = $this->getConnection();
        ConnectionProvider::set($db);

        $this->assertTrue(ConnectionProvider::has());
        $this->assertSame($db, ConnectionProvider::get());
        $this->assertSame(['default' => $db], ConnectionProvider::all());

        $db2 = $this->getConnection();
        ConnectionProvider::set($db2, 'db2');

        $this->assertTrue(ConnectionProvider::has('db2'));
        $this->assertSame($db2, ConnectionProvider::get('db2'));
        $this->assertSame(['default' => $db, 'db2' => $db2], ConnectionProvider::all());

        ConnectionProvider::remove('db2');

        $this->assertFalse(ConnectionProvider::has('db2'));
        $this->assertSame(['default' => $db], ConnectionProvider::all());

        ConnectionProvider::clear();

        $this->assertSame([], ConnectionProvider::all());
    }
}
