<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests;

use Yiisoft\Db\Connection\Connection;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Transaction\Transaction;

abstract class ConnectionTest extends DatabaseTestCase
{
    public function testConstruct(): void
    {
        $connection = $this->getConnection(false);

        $this->assertEquals($this->dsn->getDsn(), $connection->getDsn());
    }

    public function testOpenClose(): void
    {
        $connection = $this->getConnection(false, false);

        $this->assertFalse($connection->isActive());
        $this->assertNull($connection->getPDO());

        $connection->open();

        $this->assertTrue($connection->isActive());
        $this->assertInstanceOf('\\PDO', $connection->getPDO());

        $connection->close();

        $this->assertFalse($connection->isActive());
        $this->assertNull($connection->getPDO());

        $connection = new Connection($this->cache, $this->logger, $this->profiler, 'unknown::memory:');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('could not find driver');

        $connection->open();
    }

    public function testSerialize(): void
    {
        $connection = $this->getConnection(false, false);

        $connection->open();

        $serialized = \serialize($connection);

        $this->assertNotNull($connection->getPDO());

        $unserialized = \unserialize($serialized);

        $this->assertInstanceOf(Connection::class, $unserialized);
        $this->assertNull($unserialized->getPDO());
        $this->assertEquals(123, $unserialized->createCommand('SELECT 123')->queryScalar());
    }

    public function testGetDriverName(): void
    {
        $connection = $this->getConnection(false, false);

        $this->assertEquals($this->driverName, $connection->getDriverName());
    }

    public function testQuoteValue(): void
    {
        $connection = $this->getConnection(false);

        $this->assertEquals(123, $connection->quoteValue(123));
        $this->assertEquals("'string'", $connection->quoteValue('string'));
        $this->assertEquals("'It\\'s interesting'", $connection->quoteValue("It's interesting"));
    }

    public function testQuoteTableName(): void
    {
        $connection = $this->getConnection(false, false);

        $this->assertEquals('`table`', $connection->quoteTableName('table'));
        $this->assertEquals('`table`', $connection->quoteTableName('`table`'));
        $this->assertEquals('`schema`.`table`', $connection->quoteTableName('schema.table'));
        $this->assertEquals('`schema`.`table`', $connection->quoteTableName('schema.`table`'));
        $this->assertEquals('`schema`.`table`', $connection->quoteTableName('`schema`.`table`'));
        $this->assertEquals('{{table}}', $connection->quoteTableName('{{table}}'));
        $this->assertEquals('(table)', $connection->quoteTableName('(table)'));
    }

    public function testQuoteColumnName(): void
    {
        $connection = $this->getConnection(false, false);

        $this->assertEquals('`column`', $connection->quoteColumnName('column'));
        $this->assertEquals('`column`', $connection->quoteColumnName('`column`'));
        $this->assertEquals('[[column]]', $connection->quoteColumnName('[[column]]'));
        $this->assertEquals('{{column}}', $connection->quoteColumnName('{{column}}'));
        $this->assertEquals('(column)', $connection->quoteColumnName('(column)'));

        $this->assertEquals('`column`', $connection->quoteSql('[[column]]'));
        $this->assertEquals('`column`', $connection->quoteSql('{{column}}'));
    }

    public function testQuoteFullColumnName(): void
    {
        $connection = $this->getConnection(false, false);

        $this->assertEquals('`table`.`column`', $connection->quoteColumnName('table.column'));
        $this->assertEquals('`table`.`column`', $connection->quoteColumnName('table.`column`'));
        $this->assertEquals('`table`.`column`', $connection->quoteColumnName('`table`.column'));
        $this->assertEquals('`table`.`column`', $connection->quoteColumnName('`table`.`column`'));

        $this->assertEquals('[[table.column]]', $connection->quoteColumnName('[[table.column]]'));
        $this->assertEquals('{{table}}.`column`', $connection->quoteColumnName('{{table}}.column'));
        $this->assertEquals('{{table}}.`column`', $connection->quoteColumnName('{{table}}.`column`'));
        $this->assertEquals('{{table}}.[[column]]', $connection->quoteColumnName('{{table}}.[[column]]'));
        $this->assertEquals('{{%table}}.`column`', $connection->quoteColumnName('{{%table}}.column'));
        $this->assertEquals('{{%table}}.`column`', $connection->quoteColumnName('{{%table}}.`column`'));

        $this->assertEquals('`table`.`column`', $connection->quoteSql('[[table.column]]'));
        $this->assertEquals('`table`.`column`', $connection->quoteSql('{{table}}.[[column]]'));
        $this->assertEquals('`table`.`column`', $connection->quoteSql('{{table}}.`column`'));
        $this->assertEquals('`table`.`column`', $connection->quoteSql('{{%table}}.[[column]]'));
        $this->assertEquals('`table`.`column`', $connection->quoteSql('{{%table}}.`column`'));
    }

    public function testTransaction(): void
    {
        $connection = $this->getConnection(false, true, true);

        $this->assertNull($connection->getTransaction());

        $transaction = $connection->beginTransaction();

        $this->assertNotNull($connection->getTransaction());
        $this->assertTrue($transaction->isActive());

        $connection->createCommand()->insert('profile', ['description' => 'test transaction'])->execute();

        $transaction->rollBack();

        $this->assertFalse($transaction->isActive());
        $this->assertNull($connection->getTransaction());

        $this->assertEquals(0, $connection->createCommand(
            "SELECT COUNT(*) FROM profile WHERE description = 'test transaction';"
        )->queryScalar());

        $transaction = $connection->beginTransaction();

        $connection->createCommand()->insert(
            'profile',
            ['description' => 'test transaction']
        )->execute();

        $transaction->commit();

        $this->assertFalse($transaction->isActive());
        $this->assertNull($connection->getTransaction());

        $this->assertEquals(1, $connection->createCommand(
            "SELECT COUNT(*) FROM profile WHERE description = 'test transaction';"
        )->queryScalar());
    }

    public function testTransactionIsolation(): void
    {
        $connection = $this->getConnection(true);

        $transaction = $connection->beginTransaction(Transaction::READ_UNCOMMITTED);
        $transaction->commit();

        $transaction = $connection->beginTransaction(Transaction::READ_COMMITTED);
        $transaction->commit();

        $transaction = $connection->beginTransaction(Transaction::REPEATABLE_READ);
        $transaction->commit();

        $transaction = $connection->beginTransaction(Transaction::SERIALIZABLE);
        $transaction->commit();

        $this->assertTrue(true); // should not be any exception so far
    }

    public function testTransactionShortcutException(): void
    {
        $connection = $this->getConnection(true, true, true);

        $result = $connection->transaction(function (Connection $db) {
            $db->createCommand()->insert('profile', ['description' => 'test transaction shortcut'])->execute();
            return true;
        }, Transaction::READ_UNCOMMITTED);

        $this->assertTrue($result, 'transaction shortcut valid value should be returned from callback');

        $profilesCount = $connection->createCommand(
            "SELECT COUNT(*) FROM profile WHERE description = 'test transaction shortcut';"
        )->queryScalar();

        $this->assertEquals(1, $profilesCount, 'profile should be inserted in transaction shortcut');
    }

    public function testTransactionShortcutCorrect(): void
    {
        $connection = $this->getConnection(true, true, true);

        $result = $connection->transaction(function () use ($connection) {
            $connection->createCommand()->insert('profile', ['description' => 'test transaction shortcut'])->execute();
            return true;
        });

        $this->assertTrue($result, 'transaction shortcut valid value should be returned from callback');

        $profilesCount = $connection->createCommand(
            "SELECT COUNT(*) FROM profile WHERE description = 'test transaction shortcut';"
        )->queryScalar();

        $this->assertEquals(1, $profilesCount, 'profile should be inserted in transaction shortcut');
    }

    public function testTransactionShortcutCustom(): void
    {
        $connection = $this->getConnection(true, true, true);

        $result = $connection->transaction(function (Connection $db) {
            $db->createCommand()->insert('profile', ['description' => 'test transaction shortcut'])->execute();
            return true;
        }, Transaction::READ_UNCOMMITTED);

        $this->assertTrue($result, 'transaction shortcut valid value should be returned from callback');

        $profilesCount = $connection->createCommand(
            "SELECT COUNT(*) FROM profile WHERE description = 'test transaction shortcut';"
        )->queryScalar();

        $this->assertEquals(1, $profilesCount, 'profile should be inserted in transaction shortcut');
    }

    /**
     * Tests nested transactions with partial rollback.
     *
     * @see https://github.com/yiisoft/yii2/issues/9851
     */
    public function testNestedTransaction()
    {
        /** @var Connection $connection */
        $connection = $this->getConnection(true);

        $connection->transaction(function (Connection $db) {
            $this->assertNotNull($db->getTransaction());

            $db->transaction(function (Connection $db) {
                $this->assertNotNull($db->getTransaction());
                $db->getTransaction()->rollBack();
            });

            $this->assertNotNull($db->getTransaction());
        });
    }

    public function testNestedTransactionNotSupported()
    {
        $connection = $this->getConnection();

        $connection->setEnableSavepoint(false);

        $connection->transaction(function (Connection $db) {
            $this->assertNotNull($db->getTransaction());
            $this->expectException(NotSupportedException::class);
            $db->beginTransaction();
        });
    }

    public function testExceptionContainsRawQuery(): void
    {
        $connection = $this->getConnection();

        if ($connection->getTableSchema('qlog1', true) === null) {
            $connection->createCommand()->createTable('qlog1', ['id' => 'pk'])->execute();
        }

        $connection->setEmulatePrepare(true);

        // profiling and logging
        $connection->setEnableLogging(true);
        $connection->setEnableProfiling(true);

        $this->runExceptionTest($connection);

        // profiling only
        $connection->setEnableLogging(false);
        $connection->setEnableProfiling(true);

        $this->runExceptionTest($connection);

        // logging only
        $connection->setEnableLogging(true);
        $connection->setEnableProfiling(false);

        $this->runExceptionTest($connection);

        // disabled
        $connection->setEnableLogging(false);
        $connection->setEnableProfiling(false);

        $this->runExceptionTest($connection);
    }

    /**
     * @param Connection $connection
     */
    private function runExceptionTest(Connection $connection): void
    {
        $thrown = false;

        try {
            $connection->createCommand('INSERT INTO qlog1(a) VALUES(:a);', [':a' => 1])->execute();
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
            $connection->createCommand(
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
        $connection = $this->getConnection(true, false);

        $this->assertNull($connection->getTransaction());
        $this->assertNull($connection->getPDO());

        $connection->open();

        $this->assertNull($connection->getTransaction());
        $this->assertNotNull($connection->getPDO());

        $conn2 = clone $connection;

        $this->assertNull($connection->getTransaction());
        $this->assertNotNull($connection->getPDO());

        $this->assertNull($conn2->getTransaction());
        $this->assertNull($conn2->getPDO());

        $connection->beginTransaction();

        $this->assertNotNull($connection->getTransaction());
        $this->assertNotNull($connection->getPDO());

        $this->assertNull($conn2->getTransaction());
        $this->assertNull($conn2->getPDO());

        $conn3 = clone $connection;

        $this->assertNotNull($connection->getTransaction());
        $this->assertNotNull($connection->getPDO());
        $this->assertNull($conn3->getTransaction());
        $this->assertNull($conn3->getPDO());
    }

    /**
     * Test whether slave connection is recovered when call getSlavePdo() after close().
     *
     * @see https://github.com/yiisoft/yii2/issues/14165
     */
    public function testGetPdoAfterClose(): void
    {
        $connection = $this->getConnection();

        $connection->setSlaves(
            '1',
            $this->dsn->getDsn(),
            [
                'setUsername()' => [$connection->getUsername()],
                'setPassword()' => [$connection->getPassword()],
            ]
        );

        $this->assertNotNull($connection->getSlavePdo(false));

        $connection->close();

        $masterPdo = $connection->getMasterPdo();
        $this->assertNotFalse($masterPdo);
        $this->assertNotNull($masterPdo);

        $slavePdo = $connection->getSlavePdo(false);
        $this->assertNotFalse($slavePdo);
        $this->assertNotNull($slavePdo);
        $this->assertNotSame($masterPdo, $slavePdo);
    }

    public function testServerStatusCacheWorks(): void
    {
        $connection = $this->getConnection(true, false);

        $connection->setMasters(
            '1',
            $this->dsn->getDsn(),
            [
                'setUsername()' => [$connection->getUsername()],
                'setPassword()' => [$connection->getPassword()],
            ]
        );

        $connection->setShuffleMasters(false);

        $cacheKey = ['Yiisoft\Db\Connection\Connection::openFromPoolSequentially', $connection->getDsn()];

        $this->assertFalse($this->cache->has($cacheKey));

        $connection->open();

        $this->assertFalse(
            $this->cache->has($cacheKey),
            'Connection was successful – cache must not contain information about this DSN'
        );

        $connection->close();

        $connection = $this->getConnection(true, false);

        $cacheKey = ['Yiisoft\Db\Connection\Connection::openFromPoolSequentially', 'host:invalid'];

        $connection->setMasters(
            '1',
            'host:invalid',
            [
                'setUsername()' => [$connection->getUsername()],
                'setPassword()' => [$connection->getPassword()],
            ]
        );

        $connection->setShuffleMasters(true);

        try {
            $connection->open();
        } catch (InvalidConfigException $e) {
        }

        $this->assertTrue(
            $this->cache->has($cacheKey),
            'Connection was not successful – cache must contain information about this DSN'
        );

        $connection->close();
    }

    public function testServerStatusCacheCanBeDisabled(): void
    {
        $this->cache->clear();

        $connection = $this->getConnection(true, false);

        $connection->setMasters(
            '1',
            $this->dsn->getDsn(),
            [
                'setUsername()' => [$connection->getUsername()],
                'setPassword()' => [$connection->getPassword()],
            ]
        );

        $connection->setSchemaCache(null);

        $connection->setShuffleMasters(false);

        $cacheKey = ['Yiisoft\Db\Connection\Connection::openFromPoolSequentially', $connection->getDsn()];

        $this->assertFalse($this->cache->has($cacheKey));

        $connection->open();

        $this->assertFalse($this->cache->has($cacheKey), 'Caching is disabled');

        $connection->close();

        $cacheKey = ['Yiisoft\Db\Connection\Connection::openFromPoolSequentially', 'host:invalid'];

        $connection->setMasters(
            '1',
            'host:invalid',
            [
                'setUsername()' => [$connection->getUsername()],
                'setPassword()' => [$connection->getPassword()],
            ]
        );

        try {
            $connection->open();
        } catch (InvalidConfigException $e) {
        }

        $this->assertFalse($this->cache->has($cacheKey), 'Caching is disabled');

        $connection->close();
    }
}
