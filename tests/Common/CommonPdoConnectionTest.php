<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Common;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Throwable;
use Yiisoft\Db\Driver\Pdo\AbstractPdoConnection;
use Yiisoft\Db\Driver\Pdo\AbstractPdoTransaction;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Profiler\ProfilerInterface;
use Yiisoft\Db\Tests\AbstractPdoConnectionTest;
use Yiisoft\Db\Tests\Support\DbHelper;
use Yiisoft\Db\Transaction\TransactionInterface;

abstract class CommonPdoConnectionTest extends AbstractPdoConnectionTest
{
    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function testCreateCommandWithLoggerProfiler(): void
    {
        $db = $this->getConnection();

        $db->setLogger($this->getLogger());
        $db->setProfiler($this->getProfiler());
        $command = $db->createCommand('SELECT 1');

        $this->assertSame('SELECT 1', $command->getSql());
        $this->assertSame([], $command->getParams());

        $db->close();
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     * @throws Throwable
     */
    public function testCommitTransactionsWithSavepoints(): void
    {
        $db = $this->getConnection(true);

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
            )->queryScalar()
        );
        $this->assertEquals(
            '1',
            $db->createCommand(
                <<<SQL
                SELECT COUNT(*) FROM {{profile}} WHERE [[description]] = 'test transaction2'
                SQL,
            )->queryScalar()
        );
        $this->assertEquals(
            '1',
            $db->createCommand(
                <<<SQL
                SELECT COUNT(*) FROM {{profile}} WHERE [[description]] = 'test transaction3'
                SQL,
            )->queryScalar()
        );

        $db->close();
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     * @throws Throwable
     */
    public function testPartialRollbackTransactionsWithSavePoints(): void
    {
        $db = $this->getConnection(true);
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

    /**
     * @throws Exception
     * @throws NotSupportedException
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testRollbackTransactionsWithSavePoints(): void
    {
        $db = $this->getConnection(true);
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
            )->queryScalar()
        );

        $db->close();
    }

    public function testTransactionCommitNotActiveTransaction(): void
    {
        $db = $this->getConnection();

        $transaction = $db->beginTransaction();
        $db->close();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Failed to commit transaction: transaction was inactive.');

        $transaction->commit();
    }

    public function testTransactionCommitSavepoint(): void
    {
        $db = $this->getConnection();

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('log')
            ->with(
                LogLevel::INFO,
                'Transaction not committed: nested transaction not supported Yiisoft\Db\Driver\Pdo\AbstractPdoTransaction::commit'
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
        $db = $this->getConnection();

        $transaction = $db->beginTransaction();
        $db->close();

        $level = $transaction->getLevel();
        $transaction->rollBack();
        $this->assertEquals($level, $transaction->getLevel());
    }

    public function testTransactionRollbackSavepoint(): void
    {
        $db = $this->getConnection();

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('log')
            ->with(
                LogLevel::INFO,
                'Transaction not rolled back: nested transaction not supported Yiisoft\Db\Driver\Pdo\AbstractPdoTransaction::rollBack'
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
        $db = $this->getConnection();

        $transaction = $db->beginTransaction();
        $db->close();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Failed to set isolation level: transaction was inactive.');

        $transaction->setIsolationLevel(TransactionInterface::SERIALIZABLE);
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
            'getLastInsertID',
            'getQueryBuilder',
            'getQuoter',
            'getSchema',
            'getServerVersion',
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
            'getPdo',
            'getQueryBuilder',
            'getQuoter',
            'getSchema',
        ])
            ->setConstructorArgs([
                $this->getConnection()->getDriver(),
                DbHelper::getSchemaCache(),
            ])
            ->getMock();
        $db->expects(self::once())
            ->method('getPdo')
            ->willReturn(null);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('PDO cannot be initialized.');

        $db->getActivePDO();
    }

    private function getProfiler(): ProfilerInterface
    {
        return $this->createMock(ProfilerInterface::class);
    }
}
