<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Common;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Throwable;
use Yiisoft\Db\Command\CommandInterface;
use Yiisoft\Db\Connection\AbstractConnection;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Profiler\ProfilerInterface;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;
use Yiisoft\Db\Schema\QuoterInterface;
use Yiisoft\Db\Schema\SchemaInterface;
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
                'Transaction not committed: nested transaction not supported Yiisoft\Db\Driver\PDO\AbstractTransactionPDO::commit'
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
                'Transaction not rolled back: nested transaction not supported Yiisoft\Db\Driver\PDO\AbstractTransactionPDO::rollBack'
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
        $transactionMock = $this->createMock(TransactionInterface::class);
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

        $db = new class ($transactionMock) extends AbstractConnection {
            private $transactionMock;

            public function __construct($transaction)
            {
                $this->transactionMock = $transaction;
            }

            public function createCommand(string $sql = null, array $params = []): CommandInterface
            {
            }

            public function createTransaction(): TransactionInterface
            {
                return $this->transactionMock;
            }

            public function close(): void
            {
            }

            public function getCacheKey(): array
            {
            }

            public function getName(): string
            {
            }

            public function getLastInsertID(string $sequenceName = null): string
            {
            }

            public function getQueryBuilder(): QueryBuilderInterface
            {
            }

            public function getQuoter(): QuoterInterface
            {
            }

            public function getSchema(): SchemaInterface
            {
            }

            public function getServerVersion(): string
            {
            }

            public function isActive(): bool
            {
            }

            public function open(): void
            {
            }

            public function quoteValue(mixed $value): mixed
            {
            }
        };

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

    private function getProfiler(): ProfilerInterface
    {
        return $this->createMock(ProfilerInterface::class);
    }
}
