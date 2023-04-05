<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Common;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Throwable;
use Yiisoft\Db\Connection\AbstractConnection;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Tests\AbstractConnectionTest;
use Yiisoft\Db\Transaction\TransactionInterface;

abstract class CommonConnectionTest extends AbstractConnectionTest
{
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

        $db = $this->getMockBuilder(AbstractConnection::class)->onlyMethods([
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
        ])->getMock();

        $db->expects(self::once())->method('createTransaction')->willReturn($transactionMock);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('log')->with(LogLevel::ERROR);
        $db->setLogger($logger);

        $this->expectException(Exception::class);

        $db->transaction(static function () {
            throw new Exception('Test');
        });
    }

    /**
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testTransactionShortcutException(): void
    {
        $db = $this->getConnection(true);

        $this->expectException(Exception::class);

        $db->transaction(
            static function () use ($db) {
                $db->createCommand()->insert('profile', ['description' => 'test transaction shortcut'])->execute();

                throw new Exception('Exception in transaction shortcut');
            }
        );
        $profilesCount = $db->createCommand(
            <<<SQL
            SELECT COUNT(*) FROM {{profile}} WHERE [[description]] = 'test transaction shortcut'
            SQL
        )->queryScalar();

        $this->assertSame(0, $profilesCount, 'profile should not be inserted in transaction shortcut');

        $db->close();
    }
}
