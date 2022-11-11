<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Common;

use Psr\Log\NullLogger;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Tests\AbstractConnectionTest;
use Yiisoft\Db\Tests\Support\Assert;
use Yiisoft\Db\Tests\Support\TestTrait;

/**
 * @group mssql
 * @group mysql
 * @group pgsql
 * @group oracle
 * @group sqlite
 */
abstract class CommonConnectionTest extends AbstractConnectionTest
{
    use TestTrait;

    public function testExecute(): void
    {
        $db = $this->getConnectionWithData();

        $sql = <<<SQL
        INSERT INTO {{customer}}([[email]], [[name]], [[address]]) VALUES ('user4@example.com', 'user4', 'address4')
        SQL;
        $command = $db->createCommand($sql);

        $this->assertSame(1, $command->execute());

        $sql = <<<SQL
        SELECT COUNT(*) FROM {{customer}} WHERE [[name]] = 'user4'
        SQL;
        $command = $db->createCommand($sql);

        $this->assertSame(1, $command->queryScalar());

        $command = $db->createCommand('bad SQL');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('SQLSTATE[HY000]: General error: 1 near "bad": syntax error');

        $command->execute();
    }

    public function testExceptionContainsRawQuery(): void
    {
        $db = $this->getConnection();

        /* profiling and logging */
        $db->createCommand()->createTable('qlog1', ['id' => 'pk'])->execute();
        $db->setEmulatePrepare(true);
        $logger = $this->getLogger();
        $profiler = $this->getProfiler();
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

    public function testGetTableSchema(): void
    {
        $db = $this->getConnection();

        $this->assertNull($db->getTableSchema('non_existing_table'));
    }

    public function testLoggerProfiler(): void
    {
        $db = $this->getConnection();

        foreach (['qlog1', 'qlog2', 'qlog3', 'qlog4'] as $table) {
            if ($db->getTableSchema($table, true) !== null) {
                $db->createCommand()->dropTable($table)->execute();
            }
        }

        $logger = $this->getLogger();
        $profiler = $this->getProfiler();
        /* profiling and logging */
        $db->setLogger($logger);
        $db->setProfiler($profiler);

        $this->assertNotNull($logger);
        $this->assertNotNull($profiler);

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

    public function testNotProfiler(): void
    {
        $db = $this->getConnection();

        if ($db->getTableSchema('notProfiler1', true) !== null) {
            $db->createCommand()->dropTable('notProfiler1')->execute();
        }

        if ($db->getTableSchema('notProfiler2', true) !== null) {
            $db->createCommand()->dropTable('notProfiler2')->execute();
        }

        $profiler = $this->getProfiler();
        $db->notProfiler();
        $profiler->flush();
        $db->createCommand()->createTable('notProfiler1', ['id' => 'pk'])->execute();

        $this->assertCount(0, Assert::getInaccessibleProperty($profiler, 'messages'));

        $db->setProfiler($profiler);
        $db->createCommand()->createTable('notProfiler2', ['id' => 'pk'])->execute();

        $this->assertCount(1, Assert::getInaccessibleProperty($profiler, 'messages'));
    }

    public function testPartialRollbackTransactionsWithSavePoints(): void
    {
        $db = $this->getConnectionWithData();

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
            1,
            $db->createCommand(
                <<<SQL
                SELECT COUNT(*) FROM {{profile}} WHERE [[description]] = 'test transaction1'
                SQL
            )->queryScalar(),
        );
        $this->assertSame(
            0,
            $db->createCommand(
                <<<SQL
                SELECT COUNT(*) FROM {{profile}} WHERE [[description]] = 'test transaction2'
                SQL
            )->queryScalar()
        );
        $this->assertSame(
            1,
            $db->createCommand(
                <<<SQL
                SELECT COUNT(*) FROM {{profile}} WHERE [[description]] = 'test transaction3'
                SQL
            )->queryScalar(),
        );
    }

    public function testRollbackTransactionsWithSavePoints(): void
    {
        $db = $this->getConnectionWithData();

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
            0,
            $db->createCommand(
                <<<SQL
                SELECT COUNT(*) FROM {{profile}} WHERE [[description]] = 'test transaction'
                SQL
            )->queryScalar(),
        );
    }

    public function testTransaction(): void
    {
        $db = $this->getConnectionWithData();

        $db->setLogger($this->getLogger());

        $this->assertNull($db->getTransaction());

        $transaction = $db->beginTransaction();

        $this->assertNotNull($db->getTransaction());
        $this->assertTrue($transaction->isActive());

        $db->createCommand()->insert('profile', ['description' => 'test transaction'])->execute();
        $transaction->rollBack();

        $this->assertFalse($transaction->isActive());
        $this->assertNull($db->getTransaction());
        $this->assertSame(
            0,
            $db->createCommand(
                <<<SQL
                SELECT COUNT(*) FROM {{profile}} WHERE [[description]] = 'test transaction'
                SQL
            )->queryScalar()
        );

        $transaction = $db->beginTransaction();
        $db->createCommand()->insert('profile', ['description' => 'test transaction'])->execute();
        $transaction->commit();

        $this->assertFalse($transaction->isActive());
        $this->assertNull($db->getTransaction());
        $this->assertSame(
            1,
            $db->createCommand(
                <<<SQL
                SELECT COUNT(*) FROM {{profile}} WHERE [[description]] = 'test transaction'
                SQL
            )->queryScalar(),
        );
    }

    public function testTransactionShortcutCorrect(): void
    {
        $db = $this->getConnectionWithData();

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

    private function runExceptionTest(ConnectionInterface $db): void
    {
        $thrown = false;

        try {
            $db->createCommand(
                <<<SQL
                INSERT INTO qlog1(a) VALUES(:a)
                SQL,
                [':a' => 1]
            )->execute();
        } catch (Exception $e) {
            $this->assertStringContainsString(
                <<<SQL
                INSERT INTO qlog1(a) VALUES(:a)
                SQL,
                $e->getMessage(),
                'Exceptions message should contain raw SQL query: ' . $e
            );

            $thrown = true;
        }

        $this->assertTrue($thrown, 'An exception should have been thrown by the command.');

        $thrown = false;

        try {
            $db->createCommand(
                <<<SQL
                SELECT * FROM qlog1 WHERE id=:a ORDER BY nonexistingcolumn
                SQL,
                [':a' => 1]
            )->queryAll();
        } catch (Exception $e) {
            $this->assertStringContainsString(
                <<<SQL
                SELECT * FROM qlog1 WHERE id=:a ORDER BY nonexistingcolumn
                SQL,
                $e->getMessage(),
                'Exceptions message should contain raw SQL query: ' . $e
            );

            $thrown = true;
        }

        $this->assertTrue($thrown, 'An exception should have been thrown by the command.');
    }
}
