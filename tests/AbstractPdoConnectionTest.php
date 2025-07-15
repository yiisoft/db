<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests;

use PDO;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Yiisoft\Db\Driver\Pdo\PdoDriverInterface;
use Yiisoft\Db\Driver\Pdo\PdoServerInfo;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Tests\Support\TestTrait;

abstract class AbstractPdoConnectionTest extends TestCase
{
    use TestTrait;

    public function testClone(): void
    {
        $db = $this->getConnection();

        $db2 = clone $db;

        $this->assertNotSame($db, $db2);
        $this->assertNull($db2->getTransaction());
        $this->assertNull($db2->getPdo());
    }

    public function testGetDriver(): void
    {
        $driver = $this->getConnection()->getDriver();

        $this->assertInstanceOf(PdoDriverInterface::class, $driver);
    }

    public function testGetServerInfo(): void
    {
        $db = $this->getConnection();

        $this->assertInstanceOf(PdoServerInfo::class, $db->getServerInfo());
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function testOpenClose(): void
    {
        $db = $this->getConnection();

        $this->assertFalse($db->isActive());
        $this->assertNull($db->getPdo());

        $db->open();

        $this->assertTrue($db->isActive());
        $this->assertInstanceOf(PDO::class, $db->getPdo());

        $db->close();

        $this->assertFalse($db->isActive());
        $this->assertNull($db->getPdo());

        $this->setDsn('unknown::memory:');

        $db = $this->getConnection();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('could not find driver');

        $db->open();
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function testOpenCloseWithLogger(): void
    {
        $db = $this->getConnection();

        $this->assertFalse($db->isActive());
        $this->assertNull($db->getPdo());

        $db->open();

        $this->assertTrue($db->isActive());
        $this->assertInstanceOf(PDO::class, $db->getPdo());

        $logger = $this->getLogger();
        $logger->expects(self::once())->method('log');

        $db->setLogger($logger);
        $db->close();

        $this->assertFalse($db->isActive());
        $this->assertNull($db->getPdo());

        $this->setDsn('unknown::memory:');

        $db = $this->getConnection();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('could not find driver');

        $db->open();
    }

    public function testQuoteValueNotString(): void
    {
        $db = $this->getConnection();

        $value = $db->quoteValue(1);

        $this->assertSame(1, $value);
    }

    public function testSetEmulatePrepare(): void
    {
        $db = $this->getConnection();

        $this->assertNull($db->getEmulatePrepare());

        $db->setEmulatePrepare(true);

        $this->assertTrue($db->getEmulatePrepare());
    }

    protected function getLogger(): LoggerInterface|MockObject
    {
        return $this->createMock(LoggerInterface::class);
    }
}
