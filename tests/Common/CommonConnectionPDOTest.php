<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Common;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Throwable;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Tests\AbstractConnectionPDOTest;
use Yiisoft\Db\Tests\Support\DbHelper;
use Yiisoft\Db\Transaction\TransactionInterface;

abstract class CommonConnectionPDOTest extends AbstractConnectionPDOTest
{
    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function testCreateCommandWithLoggerProfiler(): void
    {
        $db = $this->getConnection();

        $db->setLogger(DbHelper::getLogger());
        $db->setProfiler(DbHelper::getProfiler());
        $command = $db->createCommand('SELECT 1');

        $this->assertSame('SELECT 1', $command->getSql());
        $this->assertSame([], $command->getParams());
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

        $db->setLogger(DbHelper::getLogger());
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
                'Transaction not committed: nested transaction not supported Yiisoft\Db\Driver\PDO\TransactionPDO::commit'
            );

        $db->beginTransaction();
        $transaction = $db->beginTransaction();
        $transaction->setLogger($logger);

        $db->setEnableSavepoint(false);

        $this->assertEquals(2, $transaction->getLevel());
        $transaction->commit();
        $this->assertEquals(1, $transaction->getLevel());
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
                'Transaction not rolled back: nested transaction not supported Yiisoft\Db\Driver\PDO\TransactionPDO::rollBack'
            );

        $db->beginTransaction();
        $transaction = $db->beginTransaction();
        $transaction->setLogger($logger);

        $db->setEnableSavepoint(false);

        $this->assertEquals(2, $transaction->getLevel());
        $transaction->rollBack();
        $this->assertEquals(1, $transaction->getLevel());
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
}
