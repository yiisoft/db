<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests;

use PDO;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Driver\PDO\PDODriverInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Tests\Support\Assert;
use Yiisoft\Db\Tests\Support\TestTrait;

abstract class AbstractConnectionPDOTest extends TestCase
{
    use TestTrait;

    public function testClone(): void
    {
        $db = $this->getConnection();

        $db2 = clone $db;

        $this->assertNotSame($db, $db2);
        $this->assertNull($db2->getTransaction());
        $this->assertNull($db2->getPDO());
    }

    public function testGetDriver(): void
    {
        $driver = $this->getConnection()->getDriver();

        $this->assertNotNull($driver);
        $this->assertInstanceOf(PDODriverInterface::class, $driver);
    }

    public function testGetLastInsertID(): void
    {
        $db = $this->getConnectionWithData();

        $command = $db->createCommand();
        $command->insert(
            'customer',
            ['name' => 'test1', 'email' => 'test1@example.com', 'address' => 'address1', 'status' => 1],
        )->execute();

        $this->assertSame('4', $db->getLastInsertID());
        $this->assertSame('4', $db->getLastInsertID('customer'));
    }

    public function testGetServerVersion(): void
    {
        $db = $this->getConnection();

        $this->assertIsString($db->getServerVersion());
    }

    public function testOpenClose(): void
    {
        $db = $this->getConnection();

        $this->assertFalse($db->isActive());
        $this->assertNull($db->getPDO());

        $db->open();

        $this->assertTrue($db->isActive());
        $this->assertInstanceOf(PDO::class, $db->getPDO());

        $db->close();

        $this->assertFalse($db->isActive());
        $this->assertNull($db->getPDO());

        $db = $this->getConnectionWithDsn('unknown::memory:');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('could not find driver');

        $db->open();
    }

    public function testOpenCloseWithLogger(): void
    {
        $db = $this->getConnection();

        $this->assertFalse($db->isActive());
        $this->assertNull($db->getPDO());

        $db->open();

        $this->assertTrue($db->isActive());
        $this->assertInstanceOf(PDO::class, $db->getPDO());

        $logger = $this->getLogger();
        $db->setLogger($logger);
        $logger->flush();
        $db->close();

        $this->assertCount(1, Assert::getInaccessibleProperty($this->getLogger(), 'messages'));
        $this->assertFalse($db->isActive());
        $this->assertNull($db->getPDO());

        $db = $this->getConnectionWithDsn('unknown::memory:');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('could not find driver');

        $db->open();
    }

    public function testQuoteValueNotString()
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
}
