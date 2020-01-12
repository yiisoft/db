<?php
declare(strict_types=1);

namespace Yiisoft\Db\Tests;

use Yiisoft\Cache\ArrayCache;
use Yiisoft\Cache\Cache;
use Yiisoft\Db\Connection;
use Yiisoft\Db\Transaction;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Tests\TestCase;

abstract class ConnectionTest extends DatabaseTestCase
{
    public function testConstruct(): void
    {
        $connection = $this->getConnection(false);

        $this->assertEquals($this->buildDSN($this->database['dsn']), $connection->getDsn());
        $this->assertEquals($this->database['username'], $connection->getUsername());
        $this->assertEquals($this->database['password'], $connection->getPassword());
    }

    public function testOpenClose(): void
    {
        $connection = $this->getConnection(false, false);

        $this->assertFalse($connection->getIsActive());
        $this->assertNull($connection->getPDO());

        $connection->open();

        $this->assertTrue($connection->getIsActive());
        $this->assertInstanceOf('\\PDO', $connection->getPDO());

        $connection->close();

        $this->assertFalse($connection->getIsActive());
        $this->assertNull($connection->getPDO());

        $dsn = [
            'driver' => 'unknown::memory:',
        ];

        $connection = new Connection($this->cache, $this->logger, $this->profiler, $dsn);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('could not find driver');

        $connection->open();
    }

    public function testSerialize()
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

    public function testGetDriverName()
    {
        $connection = $this->getConnection(false, false);

        $this->assertEquals($this->driverName, $connection->getDriverName());
    }

    public function testQuoteValue()
    {
        $connection = $this->getConnection(false);

        $this->assertEquals(123, $connection->quoteValue(123));
        $this->assertEquals("'string'", $connection->quoteValue('string'));
        $this->assertEquals("'It\\'s interesting'", $connection->quoteValue("It's interesting"));
    }

    public function testQuoteTableName()
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

    public function testQuoteColumnName()
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

    public function testQuoteFullColumnName()
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

    public function testTransaction()
    {
        $connection = $this->getConnection(false);

        $this->assertNull($connection->getTransaction());

        $transaction = $connection->beginTransaction();

        $this->assertNotNull($connection->getTransaction());
        $this->assertTrue($transaction->getIsActive());

        $connection->createCommand()->insert('profile', ['description' => 'test transaction'])->execute();

        $transaction->rollBack();

        $this->assertFalse($transaction->getIsActive());
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

        $this->assertFalse($transaction->getIsActive());
        $this->assertNull($connection->getTransaction());

        $this->assertEquals(1, $connection->createCommand(
            "SELECT COUNT(*) FROM profile WHERE description = 'test transaction';"
        )->queryScalar());
    }

    public function testTransactionIsolation()
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

    /**
     * @expectException \Exception
     */
    public function testTransactionShortcutException()
    {
        $connection = $this->getConnection(true);

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

    public function testTransactionShortcutCorrect()
    {
        $connection = $this->getConnection(true);

        $result = $connection->transaction(function () use ($connection) {
            $connection->createCommand()->insert('profile', ['description' => 'test transaction shortcut'])->execute();

            return true;
        });

        $this->assertTrue($result, 'transaction shortcut valid value should be returned from callback');

        $profilesCount = $connection->createCommand("SELECT COUNT(*) FROM profile WHERE description = 'test transaction shortcut';")->queryScalar();
        $this->assertEquals(1, $profilesCount, 'profile should be inserted in transaction shortcut');
    }

    public function testTransactionShortcutCustom()
    {
        $connection = $this->getConnection(true);

        $result = $connection->transaction(function (Connection $db) {
            $db->createCommand()->insert('profile', ['description' => 'test transaction shortcut'])->execute();

            return true;
        }, Transaction::READ_UNCOMMITTED);

        $this->assertTrue($result, 'transaction shortcut valid value should be returned from callback');

        $profilesCount = $connection->createCommand("SELECT COUNT(*) FROM profile WHERE description = 'test transaction shortcut';")->queryScalar();
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
        $connection->enableSavepoint = false;

        $connection->transaction(function (Connection $db) {
            $this->assertNotNull($db->getTransaction());
            $this->expectException(NotSupportedException::class);

            $db->beginTransaction();
        });
    }

    public function testEnableQueryLog()
    {
        $connection = $this->getConnection();

        foreach (['qlog1', 'qlog2', 'qlog3', 'qlog4'] as $table) {
            if ($connection->getTableSchema($table, true) !== null) {
                $connection->createCommand()->dropTable($table)->execute();
            }
        }

        /*// profiling and logging
        $connection->enableLogging = true;
        $connection->enableProfiling = true;

        Yii::getApp()->logger->messages = [];
        Yii::getApp()->profiler->messages = [];
        $connection->createCommand()->createTable('qlog1', ['id' => 'pk'])->execute();
        $this->assertCount(1, Yii::getApp()->logger->messages);
        $this->assertCount(1, Yii::getApp()->profiler->messages);
        $this->assertNotNull($connection->getTableSchema('qlog1', true));

        Yii::getApp()->logger->messages = [];
        Yii::getApp()->profiler->messages = [];
        $connection->createCommand('SELECT * FROM qlog1')->queryAll();
        $this->assertCount(1, Yii::getApp()->logger->messages);
        $this->assertCount(1, Yii::getApp()->profiler->messages);

        // profiling only
        $connection->enableLogging = false;
        $connection->enableProfiling = true;

        Yii::getApp()->logger->messages = [];
        Yii::getApp()->profiler->messages = [];
        $connection->createCommand()->createTable('qlog2', ['id' => 'pk'])->execute();
        $this->assertCount(0, Yii::getApp()->logger->messages);
        $this->assertCount(1, Yii::getApp()->profiler->messages);
        $this->assertNotNull($connection->getTableSchema('qlog2', true));

        Yii::getApp()->logger->messages = [];
        Yii::getApp()->profiler->messages = [];
        $connection->createCommand('SELECT * FROM qlog2')->queryAll();
        $this->assertCount(0, Yii::getApp()->logger->messages);
        $this->assertCount(1, Yii::getApp()->profiler->messages);

        // logging only
        $connection->enableLogging = true;
        $connection->enableProfiling = false;

        Yii::getApp()->logger->messages = [];
        Yii::getApp()->profiler->messages = [];
        $connection->createCommand()->createTable('qlog3', ['id' => 'pk'])->execute();
        $this->assertCount(1, Yii::getApp()->logger->messages);
        $this->assertCount(0, Yii::getApp()->profiler->messages);
        $this->assertNotNull($connection->getTableSchema('qlog3', true));

        Yii::getApp()->logger->messages = [];
        Yii::getApp()->profiler->messages = [];
        $connection->createCommand('SELECT * FROM qlog3')->queryAll();
        $this->assertCount(1, Yii::getApp()->logger->messages);
        $this->assertCount(0, Yii::getApp()->profiler->messages);

        // disabled
        $connection->enableLogging = false;
        $connection->enableProfiling = false;

        Yii::getApp()->logger->messages = [];
        Yii::getApp()->profiler->messages = [];
        $connection->createCommand()->createTable('qlog4', ['id' => 'pk'])->execute();
        $this->assertNotNull($connection->getTableSchema('qlog4', true));
        $this->assertCount(0, Yii::getApp()->logger->messages);
        $this->assertCount(0, Yii::getApp()->profiler->messages);
        $connection->createCommand('SELECT * FROM qlog4')->queryAll();
        $this->assertCount(0, Yii::getApp()->logger->messages);
        $this->assertCount(0, Yii::getApp()->profiler->messages);*/
    }

    public function testExceptionContainsRawQuery()
    {
        $connection = $this->getConnection();

        if ($connection->getTableSchema('qlog1', true) === null) {
            $connection->createCommand()->createTable('qlog1', ['id' => 'pk'])->execute();
        }

        $connection->emulatePrepare = true;

        // profiling and logging
        $connection->enableLogging = true;
        $connection->enableProfiling = true;

        $this->runExceptionTest($connection);

        // profiling only
        $connection->enableLogging = false;
        $connection->enableProfiling = true;

        $this->runExceptionTest($connection);

        // logging only
        $connection->enableLogging = true;
        $connection->enableProfiling = false;

        $this->runExceptionTest($connection);

        // disabled
        $connection->enableLogging = false;
        $connection->enableProfiling = false;

        $this->runExceptionTest($connection);
    }

    /**
     * @param Connection $connection
     */
    private function runExceptionTest($connection)
    {
        $thrown = false;

        try {
            $connection->createCommand('INSERT INTO qlog1(a) VALUES(:a);', [':a' => 1])->execute();
        } catch (Exception $e) {
            $this->assertStringContainsString(
                'INSERT INTO qlog1(a) VALUES(1);',
                $e->getMessage(),
                'Exception message should contain raw SQL query: ' . (string) $e
            );

            $thrown = true;
        }

        $this->assertTrue($thrown, 'An exception should have been thrown by the command.');

        $thrown = false;

        try {
            $connection->createCommand(
                'SELECT * FROM qlog1 WHERE id=:a ORDER BY nonexistingcolumn;', [':a' => 1]
            )->queryAll();
        } catch (Exception $e) {
            $this->assertStringContainsString(
                'SELECT * FROM qlog1 WHERE id=1 ORDER BY nonexistingcolumn;',
                $e->getMessage(),
                'Exception message should contain raw SQL query: ' . (string) $e
            );

            $thrown = true;
        }

        $this->assertTrue($thrown, 'An exception should have been thrown by the command.');
    }

    /**
     * Ensure database connection is reset on when a connection is cloned.
     * Make sure each connection element has its own PDO instance i.e. own connection to the DB.
     * Also transaction elements should not be shared between two connections.
     */
    public function testClone()
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

        if ($this->driverName === 'sqlite') {
            // in-memory sqlite should not reset PDO
            $this->assertNotNull($conn2->getPDO());
        } else {
            $this->assertNull($conn2->getPDO());
        }

        $connection->beginTransaction();

        $this->assertNotNull($connection->getTransaction());
        $this->assertNotNull($connection->getPDO());

        $this->assertNull($conn2->getTransaction());

        if ($this->driverName === 'sqlite') {
            // in-memory sqlite should not reset PDO
            $this->assertNotNull($conn2->getPDO());
        } else {
            $this->assertNull($conn2->getPDO());
        }

        $conn3 = clone $connection;

        $this->assertNotNull($connection->getTransaction());
        $this->assertNotNull($connection->getPDO());
        $this->assertNull($conn3->getTransaction());

        if ($this->driverName === 'sqlite') {
            // in-memory sqlite should not reset PDO
            $this->assertNotNull($conn3->getPDO());
        } else {
            $this->assertNull($conn3->getPDO());
        }
    }

    /**
     * Test whether slave connection is recovered when call getSlavePdo() after close().
     *
     * @see https://github.com/yiisoft/yii2/issues/14165
     */
    public function testGetPdoAfterClose()
    {
        $connection = $this->getConnection();

        $connection->slaves[] = [
            'cache'    => $this->cache,
            'logger'   => $this->logger,
            'profiler' => $this->profiler,
            'dsn'      => $connection->getDsn(),
            'username' => $connection->getUsername(),
            'password' => $connection->getPassword(),
        ];

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

    public function testDSNConfig()
    {
        $dsn = [
            'driver' => 'mysql',
            'host'   => '127.0.0.1',
            'dbname' => 'yiitest',
        ];

        $connection = new Connection($this->cache, $this->logger, $this->profiler, $dsn);
        $this->assertEquals('mysql:host=127.0.0.1;dbname=yiitest', $connection->getDsn());

        unset($dsn['driver']);

        $this->expectException(InvalidConfigException::class);

        $connection = new Connection($this->cache, $this->logger, $this->profiler, $dsn);
    }

    public function testServerStatusCacheWorks()
    {
        $connection = $this->getConnection(true, false);

        $connection->masters[] = [
            'cache'    => $this->cache,
            'logger'   => $this->logger,
            'profiler' => $this->profiler,
            'dsn'      => $connection->getDsn(),
            'username' => $connection->getUsername(),
            'password' => $connection->getPassword(),
        ];

        $connection->shuffleMasters = false;

        $cacheKey = ['Yiisoft\Db\Connection::openFromPoolSequentially', $connection->getDsn()];

        $this->assertFalse($this->cache->has($cacheKey));

        $connection->open();

        $this->assertFalse(
            $this->cache->has($cacheKey),
            'Connection was successful – cache must not contain information about this DSN'
        );

        $connection->close();

        $cacheKey = ['Yiisoft\Db\Connection::openFromPoolSequentially', 'host:invalid'];

        $connection->masters[] = [
            'cache'    => $this->cache,
            'logger'   => $this->logger,
            'profiler' => $this->profiler,
            'dsn' => 'host:invalid',
            'username' => $connection->getUsername(),
            'password' => $connection->getPassword(),
        ];

        $connection->shuffleMasters = true;

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

    public function testServerStatusCacheCanBeDisabled()
    {
        $cache = new Cache(new ArrayCache());

        $this->container->set('cache', $cache);

        $connection = $this->getConnection(true, false);

        $connection->masters[] = [
            'cache'    => $this->cache,
            'logger'   => $this->logger,
            'profiler' => $this->profiler,
            'dsn'      => $connection->getDsn(),
            'username' => $connection->getUsername(),
            'password' => $connection->getPassword(),
        ];

        $connection->shuffleMasters = false;
        $connection->serverStatusCache = false;

        $cacheKey = ['Yiisoft\Db\Connection::openFromPoolSequentially', $connection->getDsn()];

        $this->assertFalse($cache->has($cacheKey));

        $connection->open();

        $this->assertFalse($cache->has($cacheKey), 'Caching is disabled');

        $connection->close();

        $cacheKey = ['Yiisoft\Db\Connection::openFromPoolSequentially', 'host:invalid'];

        $connection->masters[0]['dsn'] = 'host:invalid';

        try {
            $connection->open();
        } catch (InvalidConfigException $e) {
        }

        $this->assertFalse($cache->has($cacheKey), 'Caching is disabled');

        $connection->close();
    }
}
