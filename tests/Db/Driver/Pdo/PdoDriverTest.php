<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Driver\Pdo;

use PDO;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Tests\Support\Assert;
use Yiisoft\Db\Tests\Support\Stub\PDODriver;

/**
 * @group db
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class PdoDriverTest extends TestCase
{
    public function testAttributes(): void
    {
        $dsn = 'sqlite::memory:';
        $pdoDriver = new PDODriver($dsn);
        $pdoDriver->attributes([PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

        $this->assertSame(
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
            Assert::getPropertyValue($pdoDriver, 'attributes'),
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

    public function testSensitiveParameter(): void
    {
        if (PHP_VERSION_ID < 80200) {
            $this->markTestSkipped('SensitiveParameterValue is not available in PHP < 8.2');
        }
        $dsn = 'sqlite::memory:';
        try {
            new PDODriver($dsn, password: null);
        } catch (\TypeError $e) {
            $this->assertTrue($e->getTrace()[0]['args'][2] instanceof \SensitiveParameterValue);
        }
        $pdoDriver = new PDODriver($dsn);
        try {
            $pdoDriver->password(null);
        } catch (\TypeError $e) {
            $this->assertTrue($e->getTrace()[0]['args'][0] instanceof \SensitiveParameterValue);
        }
    }
}
