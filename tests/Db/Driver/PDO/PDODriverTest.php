<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Driver\PDO;

use PDO;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Tests\Support\Assert;
use Yiisoft\Db\Tests\Support\Stub\PDODriver;

/**
 * @group db
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class PDODriverTest extends TestCase
{
    public function testAttributes(): void
    {
        $dsn = 'sqlite::memory:';
        $pdoDriver = new PDODriver($dsn);
        $pdoDriver->attributes([PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

        $this->assertSame(
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
            Assert::getInaccessibleProperty($pdoDriver, 'attributes'),
        );
    }

    public function testGetDriverName(): void
    {
        $dsn = 'sqlite::memory:';
        $pdoDriver = new PDODriver($dsn);

        $this->assertSame('db', $pdoDriver->getDriverName());
    }

    public function testSetCharSet(): void
    {
        $dsn = 'sqlite::memory:';
        $pdoDriver = new PDODriver($dsn);
        $pdoDriver->charset('utf8');

        $this->assertSame('utf8', $pdoDriver->getCharSet());
    }

    public function testGetPassword(): void
    {
        $dsn = 'sqlite::memory:';
        $pdoDriver = new PDODriver($dsn);
        $pdoDriver->password('password');

        $this->assertSame('password', $pdoDriver->getPassword());
    }

    public function testGetUsername(): void
    {
        $dsn = 'sqlite::memory:';
        $pdoDriver = new PDODriver($dsn);
        $pdoDriver->username('username');

        $this->assertSame('username', $pdoDriver->getUsername());
    }
}
