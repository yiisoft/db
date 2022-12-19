<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Common;

use Throwable;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Tests\AbstractConnectionPDOTest;
use Yiisoft\Db\Tests\Support\DbHelper;

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

        $transaction = $db->beginTransaction();

        $this->assertSame(1, $transaction->getLevel());

        $db->createCommand()->insert('profile', ['description' => 'test transaction1'])->execute();

        $transaction->begin();

        $this->assertSame(2, $transaction->getLevel());

        $db->createCommand()->insert('profile', ['description' => 'test transaction2'])->execute();

        $transaction->commit();

        $this->assertSame(1, $transaction->getLevel());

        $db->createCommand()->insert('profile', ['description' => 'test transaction3'])->execute();
        $transaction->commit();

        $this->assertSame(0, $transaction->getLevel());
        $this->assertFalse($transaction->isActive());
        $this->assertNull($db->getTransaction());
        $this->assertSame(
            '1',
            $db->createCommand(
                <<<SQL
                SELECT COUNT(*) FROM {{profile}} WHERE [[description]] = 'test transaction1'
                SQL,
            )->queryScalar()
        );
        $this->assertSame(
            '1',
            $db->createCommand(
                <<<SQL
                SELECT COUNT(*) FROM {{profile}} WHERE [[description]] = 'test transaction2'
                SQL,
            )->queryScalar()
        );
        $this->assertSame(
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

        $transaction = $db->beginTransaction();

        $this->assertSame(1, $transaction->getLevel());

        $db->createCommand()->insert('profile', ['description' => 'test transaction1'])->execute();
        $transaction->begin();

        $this->assertSame(2, $transaction->getLevel());

        $db->createCommand()->insert('profile', ['description' => 'test transaction2'])->execute();

        $transaction->rollBack();

        $this->assertSame(1, $transaction->getLevel());
        $this->assertTrue($transaction->isActive());

        $db->createCommand()->insert('profile', ['description' => 'test transaction3'])->execute();
        $transaction->commit();

        $this->assertSame(0, $transaction->getLevel());
        $this->assertFalse($transaction->isActive());
        $this->assertNull($db->getTransaction());
        $this->assertSame(
            '1',
            $db->createCommand(
                <<<SQL
                SELECT COUNT(*) FROM {{profile}} WHERE [[description]] = 'test transaction1'
                SQL,
            )->queryScalar(),
        );
        $this->assertSame(
            '0',
            $db->createCommand(
                <<<SQL
                SELECT COUNT(*) FROM {{profile}} WHERE [[description]] = 'test transaction2'
                SQL,
            )->queryScalar(),
        );
        $this->assertSame(
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

        $transaction = $db->beginTransaction();

        $this->assertSame(1, $transaction->getLevel());

        $db->createCommand()->insert('profile', ['description' => 'test transaction'])->execute();
        $transaction->begin();

        $this->assertSame(2, $transaction->getLevel());

        $db->createCommand()->insert('profile', ['description' => 'test transaction'])->execute();
        $transaction->rollBack();

        $this->assertSame(1, $transaction->getLevel());
        $this->assertTrue($transaction->isActive());

        $db->createCommand()->insert('profile', ['description' => 'test transaction'])->execute();
        $transaction->rollBack();

        $this->assertSame(0, $transaction->getLevel());
        $this->assertFalse($transaction->isActive());
        $this->assertNull($db->getTransaction());
        $this->assertSame(
            '0',
            $db->createCommand(
                <<<SQL
                SELECT COUNT(*) FROM {{profile}} WHERE [[description]] = 'test transaction'
                SQL,
            )->queryScalar()
        );
    }
}
