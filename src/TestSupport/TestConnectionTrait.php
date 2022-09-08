<?php

declare(strict_types=1);

namespace Yiisoft\Db\TestSupport;

use PDO;
use Psr\Log\NullLogger;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Driver\DriverInterface;
use Yiisoft\Db\Driver\PDO\ConnectionPDOInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\NotSupportedException;

use function PHPUnit\Framework\assertEquals;
use function serialize;
use function unserialize;

trait TestConnectionTrait
{
    public function testCacheKey(): void
    {
        $db = $this->getConnection();
        $this->assertEquals([$this->dsn, $this->username], $db->getCacheKey());
    }

    public function testGetName(): void
    {
        $db = $this->getConnection();
        $this->assertEquals($this->drivername, $db->getName());
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

        $db = $this->getConnection(false, 'unknown::memory:');
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('could not find driver');
        $db->open();
    }

    public function testSerialize(): void
    {
        $db = $this->getConnection();

        $db->open();

        $serialized = serialize($db);

        $this->assertNotNull($db->getPDO());

        $unserialized = unserialize($serialized);

        $this->assertInstanceOf(ConnectionPDOInterface::class, $unserialized);
        $this->assertNull($unserialized->getPDO());
        $this->assertEquals(123, $unserialized->createCommand('SELECT 123')->queryScalar());
    }

    public function testTransaction(): void
    {
        $db = $this->getConnection(true);

        $this->assertNull($db->getTransaction());

        $transaction = $db->beginTransaction();

        $this->assertNotNull($db->getTransaction());
        $this->assertTrue($transaction->isActive());

        $db->createCommand()->insert('profile', ['description' => 'test transaction'])->execute();

        $transaction->rollBack();

        $this->assertFalse($transaction->isActive());
        $this->assertNull($db->getTransaction());
        $this->assertEquals(0, $db->createCommand(
            "SELECT COUNT(*) FROM {{profile}} WHERE [[description]] = 'test transaction'"
        )->queryScalar());

        $transaction = $db->beginTransaction();

        $db->createCommand()->insert('profile', ['description' => 'test transaction'])->execute();

        $transaction->commit();

        $this->assertFalse($transaction->isActive());
        $this->assertNull($db->getTransaction());
        $this->assertEquals(1, $db->createCommand(
            "SELECT COUNT(*) FROM {{profile}} WHERE [[description]] = 'test transaction'"
        )->queryScalar());
    }

    public function testRollbackTransactionsWithSavePoints(): void
    {
        $db = $this->getConnection(true);
        $db->open();

        $transaction = $db->beginTransaction();
        assertEquals(1, $transaction->getLevel());

        $db->createCommand()->insert('profile', ['description' => 'test transaction'])->execute();

        $transaction->begin();
        assertEquals(2, $transaction->getLevel());

        $db->createCommand()->insert('profile', ['description' => 'test transaction'])->execute();

        $transaction->rollBack();
        assertEquals(1, $transaction->getLevel());
        $this->assertTrue($transaction->isActive());

        $db->createCommand()->insert('profile', ['description' => 'test transaction'])->execute();

        $transaction->rollBack();
        assertEquals(0, $transaction->getLevel());

        $this->assertFalse($transaction->isActive());
        $this->assertNull($db->getTransaction());
        $this->assertEquals(0, $db->createCommand(
            "SELECT COUNT(*) FROM {{profile}} WHERE [[description]] = 'test transaction'"
        )->queryScalar());
    }

    public function testPartialRollbackTransactionsWithSavePoints(): void
    {
        $db = $this->getConnection(true);
        $db->open();

        $transaction = $db->beginTransaction();
        assertEquals(1, $transaction->getLevel());

        $db->createCommand()->insert('profile', ['description' => 'test transaction1'])->execute();

        $transaction->begin();
        assertEquals(2, $transaction->getLevel());

        $db->createCommand()->insert('profile', ['description' => 'test transaction2'])->execute();

        $transaction->rollBack();
        assertEquals(1, $transaction->getLevel());
        $this->assertTrue($transaction->isActive());

        $db->createCommand()->insert('profile', ['description' => 'test transaction3'])->execute();

        $transaction->commit();
        assertEquals(0, $transaction->getLevel());

        $this->assertFalse($transaction->isActive());
        $this->assertNull($db->getTransaction());
        $this->assertEquals(1, $db->createCommand(
            "SELECT COUNT(*) FROM {{profile}} WHERE [[description]] = 'test transaction1'"
        )->queryScalar());
        $this->assertEquals(0, $db->createCommand(
            "SELECT COUNT(*) FROM {{profile}} WHERE [[description]] = 'test transaction2'"
        )->queryScalar());
        $this->assertEquals(1, $db->createCommand(
            "SELECT COUNT(*) FROM {{profile}} WHERE [[description]] = 'test transaction3'"
        )->queryScalar());
    }

    public function testCommitTransactionsWithSavepoints(): void
    {
        $db = $this->getConnection(true);

        $transaction = $db->beginTransaction();
        assertEquals(1, $transaction->getLevel());

        $db->createCommand()->insert('profile', ['description' => 'test transaction1'])->execute();

        $transaction->begin();
        assertEquals(2, $transaction->getLevel());

        $db->createCommand()->insert('profile', ['description' => 'test transaction2'])->execute();

        $transaction->commit();
        assertEquals(1, $transaction->getLevel());

        $db->createCommand()->insert('profile', ['description' => 'test transaction3'])->execute();

        $transaction->commit();
        assertEquals(0, $transaction->getLevel());

        $this->assertFalse($transaction->isActive());
        $this->assertNull($db->getTransaction());
        $this->assertEquals(1, $db->createCommand(
            "SELECT COUNT(*) FROM {{profile}} WHERE [[description]] = 'test transaction1'"
        )->queryScalar());
        $this->assertEquals(1, $db->createCommand(
            "SELECT COUNT(*) FROM {{profile}} WHERE [[description]] = 'test transaction2'"
        )->queryScalar());
        $this->assertEquals(1, $db->createCommand(
            "SELECT COUNT(*) FROM {{profile}} WHERE [[description]] = 'test transaction3'"
        )->queryScalar());
    }

    public function testTransactionShortcutException(): void
    {
        $db = $this->getConnection(true);

        $this->expectException(Exception::class);

        $db->transaction(function () use ($db) {
            $db->createCommand()->insert('profile', ['description' => 'test transaction shortcut'])->execute();
            throw new Exception('Exception in transaction shortcut');
        });
        $profilesCount = $db->createCommand(
            "SELECT COUNT(*) FROM {{profile}} WHERE [[description]] = 'test transaction shortcut'"
        )->queryScalar();
        $this->assertEquals(0, $profilesCount, 'profile should not be inserted in transaction shortcut');
    }

    public function testTransactionShortcutCorrect(): void
    {
        $db = $this->getConnection(true);

        $result = $db->transaction(static function () use ($db) {
            $db->createCommand()->insert('profile', ['description' => 'test transaction shortcut'])->execute();
            return true;
        });

        $this->assertTrue($result, 'transaction shortcut valid value should be returned from callback');

        $profilesCount = $db->createCommand(
            "SELECT COUNT(*) FROM {{profile}} WHERE [[description]] = 'test transaction shortcut'"
        )->queryScalar();

        $this->assertEquals(1, $profilesCount, 'profile should be inserted in transaction shortcut');
    }

    /**
     * Tests nested transactions with partial rollback.
     *
     * {@see https://github.com/yiisoft/yii2/issues/9851}
     */
    public function testNestedTransaction(): void
    {
        $db = $this->getConnection();

        $db->transaction(function (ConnectionInterface $db) {
            $this->assertNotNull($db->getTransaction());

            $db->transaction(function (ConnectionInterface $db) {
                $transaction = $db->getTransaction();
                $this->assertNotNull($transaction);
                $transaction->rollBack();
            });

            $this->assertNotNull($db->getTransaction());
        });
    }

    public function testNestedTransactionNotSupported(): void
    {
        $db = $this->getConnection();

        $db->setEnableSavepoint(false);

        $db->transaction(function (ConnectionInterface $db) {
            $this->assertNotNull($db->getTransaction());
            $this->expectException(NotSupportedException::class);
            $db->beginTransaction();
        });
    }

    public function testEnableQueryLog(): void
    {
        $db = $this->getConnection();

        foreach (['qlog1', 'qlog2', 'qlog3', 'qlog4'] as $table) {
            if ($db->getTableSchema($table, true) !== null) {
                $db->createCommand()->dropTable($table)->execute();
            }
        }

        /* profiling and logging */
        $db->setLogger($this->logger);
        $db->setProfiler($this->profiler);

        $this->assertNotNull($this->logger);
        $this->assertNotNull($this->profiler);

        $this->logger->flush();
        $this->profiler->flush();

        $db->createCommand()->createTable('qlog1', ['id' => 'pk'])->execute();

        $this->assertCount(1, $this->getInaccessibleProperty($this->logger, 'messages'));
        $this->assertCount(1, $this->getInaccessibleProperty($this->profiler, 'messages'));
        $this->assertNotNull($db->getTableSchema('qlog1', true));

        $this->logger->flush();
        $this->profiler->flush();

        $db->createCommand('SELECT * FROM {{qlog1}}')->queryAll();

        $this->assertCount(1, $this->getInaccessibleProperty($this->logger, 'messages'));
        $this->assertCount(1, $this->getInaccessibleProperty($this->profiler, 'messages'));

        /* profiling only */
        $db->setLogger(new NullLogger());
        $db->setProfiler($this->profiler);

        $this->logger->flush();
        $this->profiler->flush();

        $db->createCommand()->createTable('qlog2', ['id' => 'pk'])->execute();

        $this->assertCount(0, $this->getInaccessibleProperty($this->logger, 'messages'));
        $this->assertCount(1, $this->getInaccessibleProperty($this->profiler, 'messages'));
        $this->assertNotNull($db->getTableSchema('qlog2', true));

        $this->logger->flush();
        $this->profiler->flush();

        $db->createCommand('SELECT * FROM {{qlog2}}')->queryAll();

        $this->assertCount(0, $this->getInaccessibleProperty($this->logger, 'messages'));
        $this->assertCount(1, $this->getInaccessibleProperty($this->profiler, 'messages'));

        /* logging only */
        $db->setLogger($this->logger);
        $db->notProfiler();

        $this->logger->flush();
        $this->profiler->flush();

        $db->createCommand()->createTable('qlog3', ['id' => 'pk'])->execute();

        $this->assertCount(1, $this->getInaccessibleProperty($this->logger, 'messages'));
        $this->assertCount(0, $this->getInaccessibleProperty($this->profiler, 'messages'));
        $this->assertNotNull($db->getTableSchema('qlog3', true));

        $this->logger->flush();
        $this->profiler->flush();

        $db->createCommand('SELECT * FROM {{qlog3}}')->queryAll();

        $this->assertCount(1, $this->getInaccessibleProperty($this->logger, 'messages'));
        $this->assertCount(0, $this->getInaccessibleProperty($this->profiler, 'messages'));

        /* disabled */
        $db->setLogger(new NullLogger());
        $db->notProfiler();

        $this->logger->flush();
        $this->profiler->flush();

        $db->createCommand()->createTable('qlog4', ['id' => 'pk'])->execute();

        $this->assertNotNull($db->getTableSchema('qlog4', true));
        $this->assertCount(0, $this->getInaccessibleProperty($this->logger, 'messages'));
        $this->assertCount(0, $this->getInaccessibleProperty($this->profiler, 'messages'));

        $db->createCommand('SELECT * FROM {{qlog4}}')->queryAll();

        $this->assertCount(0, $this->getInaccessibleProperty($this->logger, 'messages'));
        $this->assertCount(0, $this->getInaccessibleProperty($this->profiler, 'messages'));
    }

    public function testExceptionContainsRawQuery(): void
    {
        $db = $this->getConnection();

        if ($db->getTableSchema('qlog1', true) === null) {
            $db->createCommand()->createTable('qlog1', ['id' => 'pk'])->execute();
        }

        $db->setEmulatePrepare(true);

        /* profiling and logging */
        $db->setLogger($this->logger);
        $db->setProfiler($this->profiler);

        $this->runExceptionTest($db);

        /* profiling only */
        $db->setLogger(new NullLogger());
        $db->setProfiler($this->profiler);

        $this->runExceptionTest($db);

        /* logging only */
        $db->setLogger($this->logger);
        $db->notProfiler();

        $this->runExceptionTest($db);

        /* disabled */
        $db->setLogger(new NullLogger());
        $db->notProfiler();

        $this->runExceptionTest($db);
    }

    /**
     * @param ConnectionInterface $db
     */
    private function runExceptionTest(ConnectionInterface $db): void
    {
        $thrown = false;

        try {
            $db->createCommand('INSERT INTO qlog1(a) VALUES(:a);', [':a' => 1])->execute();
        } catch (Exception $e) {
            $this->assertStringContainsString(
                'INSERT INTO qlog1(a) VALUES(1);',
                $e->getMessage(),
                'Exceptions message should contain raw SQL query: ' . (string) $e
            );

            $thrown = true;
        }

        $this->assertTrue($thrown, 'An exception should have been thrown by the command.');

        $thrown = false;

        try {
            $db->createCommand(
                'SELECT * FROM qlog1 WHERE id=:a ORDER BY nonexistingcolumn;',
                [':a' => 1]
            )->queryAll();
        } catch (Exception $e) {
            $this->assertStringContainsString(
                'SELECT * FROM qlog1 WHERE id=1 ORDER BY nonexistingcolumn;',
                $e->getMessage(),
                'Exceptions message should contain raw SQL query: ' . (string) $e
            );

            $thrown = true;
        }

        $this->assertTrue($thrown, 'An exception should have been thrown by the command.');
    }

    /**
     * Ensure database connection is reset on when a connection is cloned.
     *
     * Make sure each connection element has its own PDO instance i.e. own connection to the DB.
     * Also transaction elements should not be shared between two connections.
     */
    public function testClone(): void
    {
        $db = $this->getConnection();

        $this->assertNull($db->getTransaction());
        $this->assertNull($db->getPDO());

        $db->open();

        $this->assertNull($db->getTransaction());
        $this->assertNotNull($db->getPDO());

        $conn2 = clone $db;

        $this->assertNull($db->getTransaction());
        $this->assertNotNull($db->getPDO());

        $this->assertNull($conn2->getTransaction());
        $this->assertNull($conn2->getPDO());

        $db->beginTransaction();

        $this->assertNotNull($db->getTransaction());
        $this->assertNotNull($db->getPDO());

        $this->assertNull($conn2->getTransaction());
        $this->assertNull($conn2->getPDO());

        $conn3 = clone $db;

        $this->assertNotNull($db->getTransaction());
        $this->assertNotNull($db->getPDO());
        $this->assertNull($conn3->getTransaction());
        $this->assertNull($conn3->getPDO());
    }

    public function testGetDriver(): void
    {
        $db = $this->getConnection();
        $this->assertInstanceOf(DriverInterface::class, $db->getDriver());
    }
}
