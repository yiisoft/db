<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Common;

use PDO;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Yiisoft\Db\Driver\Pdo\AbstractPdoConnection;
use Yiisoft\Db\Driver\Pdo\AbstractPdoTransaction;
use Yiisoft\Db\Driver\Pdo\PdoDriverInterface;
use Yiisoft\Db\Driver\Pdo\PdoServerInfo;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Profiler\ProfilerInterface;
use Yiisoft\Db\Tests\Support\IntegrationTestCase;
use Yiisoft\Db\Tests\Support\TestHelper;
use Yiisoft\Db\Transaction\TransactionInterface;

abstract class CommonPdoConnectionTest extends IntegrationTestCase
{
    public function testClone(): void
    {
        $db = $this->getSharedConnection();

        $db2 = clone $db;

        $this->assertNotSame($db, $db2);
        $this->assertNull($db2->getTransaction());
        $this->assertNull($db2->getPdo());
    }

    public function testGetDriver(): void
    {
        $db = $this->getSharedConnection();
        $driver = $db->getDriver();

        $this->assertInstanceOf(PdoDriverInterface::class, $driver);
    }

    public function testGetServerInfo(): void
    {
        $db = $this->getSharedConnection();

        $this->assertInstanceOf(PdoServerInfo::class, $db->getServerInfo());
    }

    public function testOpenClose(): void
    {
        $db = $this->createConnection();

        $this->assertFalse($db->isActive());
        $this->assertNull($db->getPdo());

        $db->open();

        $this->assertTrue($db->isActive());
        $this->assertInstanceOf(PDO::class, $db->getPdo());

        $db->close();

        $this->assertFalse($db->isActive());
        $this->assertNull($db->getPdo());
    }

    public function testOpenCloseWithLogger(): void
    {
        $db = $this->createConnection();

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
    }

    public function testQuoteValueNotString(): void
    {
        $db = $this->getSharedConnection();

        $value = $db->quoteValue(1);

        $this->assertSame(1, $value);
    }

    public function testSetEmulatePrepare(): void
    {
        $db = $this->getSharedConnection();

        $this->assertNull($db->getEmulatePrepare());

        $db->setEmulatePrepare(true);

        $this->assertTrue($db->getEmulatePrepare());
    }

    public function testCreateCommandWithLoggerProfiler(): void
    {
        $db = $this->createConnection();

        $db->setLogger($this->getLogger());
        $db->setProfiler($this->getProfiler());
        $command = $db->createCommand('SELECT 1');

        $this->assertSame('SELECT 1', $command->getSql());
        $this->assertSame([], $command->getParams());

        $db->close();
    }

    public function testCommitTransactionsWithSavepoints(): void
    {
        $db = $this->createConnection();
        $this->loadFixture();

        $db->setLogger($this->getLogger());
        $command = $db->createCommand();
        $transaction = $db->beginTransaction();

        $this->assertSame(1, $transaction->getLevel());

        $command->insert('profile', ['description' => 'test transaction1'])->execute();
        $transaction->begin();

        $this->assertSame(2, $transaction->getLevel());

        $command->insert('profile', ['description' => 'test transaction2'])->execute();
        $transaction->commit();

        $this->assertSame(1, $transaction->getLevel());

        $command->insert('profile', ['description' => 'test transaction3'])->execute();
        $transaction->commit();

        $this->assertSame(0, $transaction->getLevel());
        $this->assertFalse($transaction->isActive());
        $this->assertNull($db->getTransaction());
        $this->assertEquals(
            '1',
            $db->createCommand(
                <<<SQL
                SELECT COUNT(*) FROM {{profile}} WHERE [[description]] = 'test transaction1'
                SQL,
            )->queryScalar(),
        );
        $this->assertEquals(
            '1',
            $db->createCommand(
                <<<SQL
                SELECT COUNT(*) FROM {{profile}} WHERE [[description]] = 'test transaction2'
                SQL,
            )->queryScalar(),
        );
        $this->assertEquals(
            '1',
            $db->createCommand(
                <<<SQL
                SELECT COUNT(*) FROM {{profile}} WHERE [[description]] = 'test transaction3'
                SQL,
            )->queryScalar(),
        );

        $db->close();
    }

    public function testPartialRollbackTransactionsWithSavePoints(): void
    {
        $db = $this->createConnection();
        $this->loadFixture();

        $db->open();

        $command = $db->createCommand();
        $transaction = $db->beginTransaction();

        $this->assertSame(1, $transaction->getLevel());

        $command->insert('profile', ['description' => 'test transaction1'])->execute();
        $transaction->begin();

        $this->assertSame(2, $transaction->getLevel());

        $command->insert('profile', ['description' => 'test transaction2'])->execute();
        $transaction->rollBack();

        $this->assertSame(1, $transaction->getLevel());
        $this->assertTrue($transaction->isActive());

        $command->insert('profile', ['description' => 'test transaction3'])->execute();
        $transaction->commit();

        $this->assertSame(0, $transaction->getLevel());
        $this->assertFalse($transaction->isActive());
        $this->assertNull($db->getTransaction());
        $this->assertEquals(
            '1',
            $db->createCommand(
                <<<SQL
                SELECT COUNT(*) FROM {{profile}} WHERE [[description]] = 'test transaction1'
                SQL,
            )->queryScalar(),
        );
        $this->assertEquals(
            '0',
            $db->createCommand(
                <<<SQL
                SELECT COUNT(*) FROM {{profile}} WHERE [[description]] = 'test transaction2'
                SQL,
            )->queryScalar(),
        );
        $this->assertEquals(
            '1',
            $db->createCommand(
                <<<SQL
                SELECT COUNT(*) FROM {{profile}} WHERE [[description]] = 'test transaction3'
                SQL,
            )->queryScalar(),
        );

        $db->close();
    }

    public function testRollbackTransactionsWithSavePoints(): void
    {
        $db = $this->createConnection();
        $this->loadFixture();
        $db->open();

        $command = $db->createCommand();
        $transaction = $db->beginTransaction();

        $this->assertSame(1, $transaction->getLevel());

        $command->insert('profile', ['description' => 'test transaction'])->execute();
        $transaction->begin();

        $this->assertSame(2, $transaction->getLevel());

        $command->insert('profile', ['description' => 'test transaction'])->execute();
        $transaction->rollBack();

        $this->assertSame(1, $transaction->getLevel());
        $this->assertTrue($transaction->isActive());

        $command->insert('profile', ['description' => 'test transaction'])->execute();
        $transaction->rollBack();

        $this->assertSame(0, $transaction->getLevel());
        $this->assertFalse($transaction->isActive());
        $this->assertNull($db->getTransaction());
        $this->assertEquals(
            '0',
            $db->createCommand(
                <<<SQL
                SELECT COUNT(*) FROM {{profile}} WHERE [[description]] = 'test transaction'
                SQL,
            )->queryScalar(),
        );

        $db->close();
    }

    public function testTransactionCommitNotActiveTransaction(): void
    {
        $db = $this->createConnection();

        $transaction = $db->beginTransaction();
        $db->close();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Failed to commit transaction: transaction was inactive.');
        $transaction->commit();
    }

    public function testTransactionCommitSavepoint(): void
    {
        $db = $this->createConnection();

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('log')
            ->with(
                LogLevel::INFO,
                'Transaction not committed: nested transaction not supported Yiisoft\Db\Driver\Pdo\AbstractPdoTransaction::commit',
            );

        $db->beginTransaction();
        $transaction = $db->beginTransaction();
        $transaction->setLogger($logger);

        $db->setEnableSavepoint(false);

        $this->assertEquals(2, $transaction->getLevel());
        $transaction->commit();
        $this->assertEquals(1, $transaction->getLevel());

        $db->close();
    }

    public function testTransactionRollbackNotActiveTransaction(): void
    {
        $db = $this->createConnection();

        $transaction = $db->beginTransaction();
        $db->close();

        $level = $transaction->getLevel();
        $transaction->rollBack();
        $this->assertEquals($level, $transaction->getLevel());
    }

    public function testTransactionRollbackSavepoint(): void
    {
        $db = $this->createConnection();

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('log')
            ->with(
                LogLevel::INFO,
                'Transaction not rolled back: nested transaction not supported Yiisoft\Db\Driver\Pdo\AbstractPdoTransaction::rollBack',
            );

        $db->beginTransaction();
        $transaction = $db->beginTransaction();
        $transaction->setLogger($logger);

        $db->setEnableSavepoint(false);

        $this->assertEquals(2, $transaction->getLevel());
        $transaction->rollBack();
        $this->assertEquals(1, $transaction->getLevel());

        $db->close();
    }

    public function testTransactionSetIsolationLevel(): void
    {
        $db = $this->createConnection();

        $transaction = $db->beginTransaction();
        $db->close();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Failed to set isolation level: transaction was inactive.');

        $transaction->setIsolationLevel(TransactionInterface::SERIALIZABLE);

        $db->close();
    }

    public function testTransactionRollbackTransactionOnLevel(): void
    {
        $transactionMock = $this->createMock(AbstractPdoTransaction::class);
        $transactionMock->expects(self::once())
            ->method('isActive')
            ->willReturn(true);
        $transactionMock->expects(self::exactly(2))
            ->method('getLevel')
            ->willReturn(0);
        $transactionMock->expects(self::once())
            ->method('rollBack')
            ->willThrowException(new Exception('rollbackTransactionOnLevel'))
        ;

        $db = $this->getMockBuilder(AbstractPdoConnection::class)->onlyMethods([
            'createTransaction',
            'createCommand',
            'close',
            'getDriverName',
            'getLastInsertId',
            'getQueryBuilder',
            'getQuoter',
            'getSchema',
            'getServerInfo',
            'isActive',
            'open',
            'quoteValue',
        ])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $db->expects(self::once())
            ->method('createTransaction')
            ->willReturn($transactionMock);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('log')
            ->with(LogLevel::ERROR);

        $db->setLogger($logger);

        $this->expectException(Exception::class);
        $db->transaction(static function () {
            throw new Exception('Test');
        });
    }

    public function testGetActivePdo(): void
    {
        $db = $this->getMockBuilder(AbstractPdoConnection::class)->onlyMethods([
            'createCommand',
            'createTransaction',
            'getColumnFactory',
            'getPdo',
            'getQueryBuilder',
            'getQuoter',
            'getSchema',
        ])
            ->setConstructorArgs([
                $this->getSharedConnection()->getDriver(),
                TestHelper::createMemorySchemaCache(),
            ])
            ->getMock();
        $db->expects(self::once())
            ->method('getPdo')
            ->willReturn(null);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('PDO cannot be initialized.');

        $db->getActivePdo();
    }

    protected function getLogger(): LoggerInterface|MockObject
    {
        return $this->createMock(LoggerInterface::class);
    }

    private function getProfiler(): ProfilerInterface
    {
        return $this->createMock(ProfilerInterface::class);
    }
}
