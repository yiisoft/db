<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests;

use PDO;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Driver\DriverInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Tests\Support\Assert;
use Yiisoft\Log\Logger;
use Yiisoft\Profiler\Profiler;

use function serialize;
use function unserialize;

abstract class AbstractConnectionTest extends TestCase
{
    public function testCacheKey(): void
    {
        $db = $this->getConnection();

        $driver = $db->getDriver();
        $dsn = $driver->getDsn();
        $username = $driver->getUsername();

        $this->assertSame([$dsn, $username], $db->getCacheKey());
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

    public function testCommitTransactionsWithSavepoints(): void
    {
        $db = $this->getConnection();

        $db->createCommand('DELETE FROM {{profile}}')->execute();
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
                "SELECT COUNT(*) FROM {{profile}} WHERE [[description]] = 'test transaction1'"
            )->queryScalar(),
        );
        $this->assertSame(
            '1',
            $db->createCommand(
                "SELECT COUNT(*) FROM {{profile}} WHERE [[description]] = 'test transaction2'"
            )->queryScalar(),
        );
        $this->assertSame(
            '1',
            $db->createCommand(
                "SELECT COUNT(*) FROM {{profile}} WHERE [[description]] = 'test transaction3'"
            )->queryScalar(),
        );
    }

    public function testEnableQueryLog(): void
    {
        $db = $this->getConnection();

        foreach (['qlog1', 'qlog2', 'qlog3', 'qlog4'] as $table) {
            if ($db->getTableSchema($table, true) !== null) {
                $db->createCommand()->dropTable($table)->execute();
            }
        }

        $logger = new Logger();
        $profiler = new Profiler($logger);

        /* profiling and logging */
        $db->setLogger($logger);
        $db->setProfiler($profiler);
        $logger->flush();
        $profiler->flush();
        $db->createCommand()->createTable('qlog1', ['id' => 'pk'])->execute();

        $this->assertCount(1, Assert::getInaccessibleProperty($logger, 'messages'));
        $this->assertCount(1, Assert::getInaccessibleProperty($profiler, 'messages'));
        $this->assertNotNull($db->getTableSchema('qlog1', true));

        $logger->flush();
        $profiler->flush();
        $db->createCommand('SELECT * FROM {{qlog1}}')->queryAll();

        $this->assertCount(1, Assert::getInaccessibleProperty($logger, 'messages'));
        $this->assertCount(1, Assert::getInaccessibleProperty($profiler, 'messages'));

        /* profiling only */
        $db->setLogger(new NullLogger());
        $db->setProfiler($profiler);
        $logger->flush();
        $profiler->flush();
        $db->createCommand()->createTable('qlog2', ['id' => 'pk'])->execute();

        $this->assertCount(0, Assert::getInaccessibleProperty($logger, 'messages'));
        $this->assertCount(1, Assert::getInaccessibleProperty($profiler, 'messages'));
        $this->assertNotNull($db->getTableSchema('qlog2', true));

        $logger->flush();
        $profiler->flush();
        $db->createCommand('SELECT * FROM {{qlog2}}')->queryAll();

        $this->assertCount(0, Assert::getInaccessibleProperty($logger, 'messages'));
        $this->assertCount(1, Assert::getInaccessibleProperty($profiler, 'messages'));

        /* logging only */
        $db->setLogger($logger);
        $db->notProfiler();
        $logger->flush();
        $profiler->flush();
        $db->createCommand()->createTable('qlog3', ['id' => 'pk'])->execute();

        $this->assertCount(1, Assert::getInaccessibleProperty($logger, 'messages'));
        $this->assertCount(0, Assert::getInaccessibleProperty($profiler, 'messages'));
        $this->assertNotNull($db->getTableSchema('qlog3', true));

        $logger->flush();
        $profiler->flush();
        $db->createCommand('SELECT * FROM {{qlog3}}')->queryAll();

        $this->assertCount(1, Assert::getInaccessibleProperty($logger, 'messages'));
        $this->assertCount(0, Assert::getInaccessibleProperty($profiler, 'messages'));

        /* disabled */
        $db->setLogger(new NullLogger());
        $db->notProfiler();

        $logger->flush();
        $profiler->flush();

        $db->createCommand()->createTable('qlog4', ['id' => 'pk'])->execute();

        $this->assertNotNull($db->getTableSchema('qlog4', true));
        $this->assertCount(0, Assert::getInaccessibleProperty($logger, 'messages'));
        $this->assertCount(0, Assert::getInaccessibleProperty($profiler, 'messages'));

        $db->createCommand('SELECT * FROM {{qlog4}}')->queryAll();

        $this->assertCount(0, Assert::getInaccessibleProperty($logger, 'messages'));
        $this->assertCount(0, Assert::getInaccessibleProperty($profiler, 'messages'));
    }

    public function testExceptionContainsRawQuery(): void
    {
        $db = $this->getConnection();

        if ($db->getTableSchema('qlog1', true) === null) {
            $db->createCommand()->createTable('qlog1', ['id' => 'pk'])->execute();
        }

        $db->setEmulatePrepare(true);
        $logger = new Logger();
        $profiler = new Profiler($logger);

        /* profiling and logging */
        $db->setLogger($logger);
        $db->setProfiler($profiler);

        $this->runExceptionTest($db);

        /* profiling only */
        $db->setLogger(new NullLogger());
        $db->setProfiler($profiler);

        $this->runExceptionTest($db);

        /* logging only */
        $db->setLogger($logger);
        $db->notProfiler();

        $this->runExceptionTest($db);

        /* disabled */
        $db->setLogger(new NullLogger());
        $db->notProfiler();

        $this->runExceptionTest($db);
    }

    public function testGetDriver(): void
    {
        $db = $this->getConnection();

        $this->assertInstanceOf(DriverInterface::class, $db->getDriver());
    }

    /**
     * Tests nested transactions with partial rollback.
     *
     * {@see https://github.com/yiisoft/yii2/issues/9851}
     */
    public function testNestedTransaction(): void
    {
        $db = $this->getConnection();

        $db->transaction(
            static function (ConnectionInterface $db): void {
                self::assertNotNull($db->getTransaction());

                $db->transaction(
                    static function (ConnectionInterface $db): void {
                        $transaction = $db->getTransaction();
                        self::assertNotNull($transaction);
                        $transaction->rollBack();
                    }
                );

                self::assertNotNull($db->getTransaction());
            }
        );
    }

    public function testNestedTransactionNotSupported(): void
    {
        $db = $this->getConnection();

        $db->setEnableSavepoint(false);

        $db->transaction(
            function (ConnectionInterface $db): void {
                $this->assertNotNull($db->getTransaction());
                $this->expectException(NotSupportedException::class);
                $db->beginTransaction();
            }
        );
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

    public function testPartialRollbackTransactionsWithSavePoints(): void
    {
        $db = $this->getConnection();

        $db->createCommand('DELETE FROM {{profile}}')->execute();
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
                "SELECT COUNT(*) FROM {{profile}} WHERE [[description]] = 'test transaction1'"
            )->queryScalar(),
        );
        $this->assertSame(
            '0',
            $db->createCommand(
                "SELECT COUNT(*) FROM {{profile}} WHERE [[description]] = 'test transaction2'"
            )->queryScalar(),
        );
        $this->assertSame(
            '1',
            $db->createCommand(
                "SELECT COUNT(*) FROM {{profile}} WHERE [[description]] = 'test transaction3'"
            )->queryScalar(),
        );
    }

    public function testRollbackTransactionsWithSavePoints(): void
    {
        $db = $this->getConnection();

        $db->createCommand('DELETE FROM {{profile}}')->execute();
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
                "SELECT COUNT(*) FROM {{profile}} WHERE [[description]] = 'test transaction'"
            )->queryScalar(),
        );
    }

    public function testSerialize(): void
    {
        $db = $this->getConnection();

        $db->open();
        $serialized = serialize($db);

        $this->assertNotNull($db->getPDO());

        $unserialized = unserialize($serialized);

        $this->assertInstanceOf(ConnectionInterface::class, $unserialized);
        $this->assertNull($unserialized->getPDO());
        $this->assertSame('123', $unserialized->createCommand('SELECT 123')->queryScalar());
    }

    public function testTransaction(): void
    {
        $db = $this->getConnection();

        $db->createCommand('DELETE FROM {{profile}}')->execute();

        $this->assertNull($db->getTransaction());

        $transaction = $db->beginTransaction();

        $this->assertNotNull($db->getTransaction());
        $this->assertTrue($transaction->isActive());

        $db->createCommand()->insert('profile', ['description' => 'test transaction'])->execute();

        $transaction->rollBack();

        $this->assertFalse($transaction->isActive());
        $this->assertNull($db->getTransaction());
        $this->assertSame(
            '0',
            $db->createCommand(
                "SELECT COUNT(*) FROM {{profile}} WHERE [[description]] = 'test transaction'"
            )->queryScalar(),
        );

        $transaction = $db->beginTransaction();
        $db->createCommand()->insert('profile', ['description' => 'test transaction'])->execute();
        $transaction->commit();

        $this->assertFalse($transaction->isActive());
        $this->assertNull($db->getTransaction());
        $this->assertSame(
            '1',
            $db->createCommand(
                "SELECT COUNT(*) FROM {{profile}} WHERE [[description]] = 'test transaction'"
            )->queryScalar()
        );
    }

    public function testTransactionShortcutCorrect(): void
    {
        $db = $this->getConnection();

        $result = $db->transaction(
            static function (ConnectionInterface $db): bool {
                $db->createCommand()->insert('profile', ['description' => 'test transaction shortcut'])->execute();
                return true;
            }
        );

        $this->assertTrue($result, 'transaction shortcut valid value should be returned from callback');

        $profilesCount = $db->createCommand(
            "SELECT COUNT(*) FROM {{profile}} WHERE [[description]] = 'test transaction shortcut'"
        )->queryScalar();

        $this->assertSame('1', $profilesCount, 'profile should be inserted in transaction shortcut');
    }

    public function testTransactionShortcutException(): void
    {
        $db = $this->getConnection();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Exception in transaction shortcut');
        $db->transaction(
            static function (ConnectionInterface $db): void {
                $db->createCommand()->insert('profile', ['description' => 'test transaction shortcut'])->execute();
                throw new Exception('Exception in transaction shortcut');
            }
        );
    }

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
}
