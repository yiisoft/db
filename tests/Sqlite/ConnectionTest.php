<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Sqlite;

use Yiisoft\Db\Drivers\Connection;
use Yiisoft\Db\Exceptions\InvalidConfigException;
use Yiisoft\Db\Transactions\Transaction;
use Yiisoft\Db\Tests\ConnectionTest as AbstractConnectionTest;

final class ConnectionTest extends AbstractConnectionTest
{
    protected ?string $driverName = 'sqlite';

    public function testConstruct(): void
    {
        $connection = $this->getConnection(false);

        $this->assertEquals($this->databases['dsn'], $connection->getDsn());
    }

    /**
     * Test whether slave connection is recovered when call getSlavePdo() after close().
     *
     * @see https://github.com/yiisoft/yii2/issues/14165
     */
    public function testGetPdoAfterClose(): void
    {
        $connection = $this->getConnection();

        $connection->slaves[] = [
            'dsn' => $this->databases['dsn'],
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

    public function testServerStatusCacheWorks(): void
    {
        $connection = $this->getConnection(true, false);

        $connection->masters[] = [
            'dsn' => $this->databases['dsn'],
        ];

        $connection->shuffleMasters = false;

        $cacheKey = ['Yiisoft\Db\Drivers\Connection::openFromPoolSequentially', $connection->getDsn()];

        $this->assertFalse($this->cache->has($cacheKey));

        $connection->open();

        $this->assertFalse(
            $this->cache->has($cacheKey),
            'Connection was successful – cache must not contain information about this DSN'
        );

        $connection->close();

        $connection = $this->getConnection(true, false);

        $cacheKey = ['Yiisoft\Db\Drivers\Connection::openFromPoolSequentially', 'host:invalid'];

        $connection->masters[] = [
            'dsn' => 'host:invalid',
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

    public function testServerStatusCacheCanBeDisabled(): void
    {
        $this->cache->clear();

        $connection = $this->getConnection(true, false);

        $connection->masters[] = [
            'dsn' => $this->databases['dsn'],
        ];

        $connection->setSchemaCache(null);

        $connection->shuffleMasters = false;

        $cacheKey = ['Yiisoft\Db\Drivers\Connection::openFromPoolSequentially', $connection->getDsn()];

        $this->assertFalse($this->cache->has($cacheKey));

        $connection->open();

        $this->assertFalse($this->cache->has($cacheKey), 'Caching is disabled');

        $connection->close();

        $cacheKey = ['Yiisoft\Db\Drivers\Connection::openFromPoolSequentially', 'host:invalid'];

        $connection->masters[] = [
            'dsn' => 'host:invalid',
        ];

        try {
            $connection->open();
        } catch (InvalidConfigException $e) {
        }

        $this->assertFalse($this->cache->has($cacheKey), 'Caching is disabled');

        $connection->close();
    }

    public function testQuoteValue(): void
    {
        $connection = $this->getConnection(false);
        $this->assertEquals(123, $connection->quoteValue(123));
        $this->assertEquals("'string'", $connection->quoteValue('string'));
        $this->assertEquals("'It''s interesting'", $connection->quoteValue("It's interesting"));
    }

    public function testTransactionIsolation(): void
    {
        $connection = $this->getConnection(true);

        $transaction = $connection->beginTransaction(Transaction::READ_UNCOMMITTED);
        $transaction->rollBack();

        $transaction = $connection->beginTransaction(Transaction::SERIALIZABLE);
        $transaction->rollBack();

        $this->assertTrue(true); // No exceptions means test is passed.
    }

    public function testMasterSlave(): void
    {
        $counts = [[0, 2], [1, 2], [2, 2]];

        foreach ($counts as $count) {
            [$masterCount, $slaveCount] = $count;

            $db = $this->prepareMasterSlave($masterCount, $slaveCount);

            $this->assertInstanceOf(Connection::class, $db->getSlave());
            $this->assertTrue($db->getSlave()->getIsActive());
            $this->assertFalse($db->getIsActive());

            // test SELECT uses slave
            $this->assertEquals(2, $db->createCommand('SELECT COUNT(*) FROM profile')->queryScalar());
            $this->assertFalse($db->getIsActive());

            // test UPDATE uses master
            $db->createCommand("UPDATE profile SET description='test' WHERE id=1")->execute();
            $this->assertTrue($db->getIsActive());

            if ($masterCount > 0) {
                $this->assertInstanceOf(Connection::class, $db->getMaster());
                $this->assertTrue($db->getMaster()->getIsActive());
            } else {
                $this->assertNull($db->getMaster());
            }

            $this->assertNotEquals(
                'test',
                $db->createCommand('SELECT description FROM profile WHERE id=1')->queryScalar()
            );

            $result = $db->useMaster(static function (Connection $db) {
                return $db->createCommand('SELECT description FROM profile WHERE id=1')->queryScalar();
            });

            $this->assertEquals('test', $result);
        }
    }

    public function testMastersShuffled(): void
    {
        $mastersCount = 2;
        $slavesCount = 2;
        $retryPerNode = 10;

        $nodesCount = $mastersCount + $slavesCount;

        $hit_slaves = $hit_masters = [];

        for ($i = $nodesCount * $retryPerNode; $i-- > 0;) {
            $db = $this->prepareMasterSlave($mastersCount, $slavesCount);
            $db->shuffleMasters = true;

            $hit_slaves[$db->getSlave()->getDsn()] = true;
            $hit_masters[$db->getMaster()->getDsn()] = true;

            if (\count($hit_slaves) === $slavesCount && \count($hit_masters) === $mastersCount) {
                break;
            }
        }

        $this->assertCount($mastersCount, $hit_masters, 'all masters hit');
        $this->assertCount($slavesCount, $hit_slaves, 'all slaves hit');
    }

    public function testMastersSequential(): void
    {
        $mastersCount = 2;
        $slavesCount = 2;
        $retryPerNode = 10;

        $nodesCount = $mastersCount + $slavesCount;

        $hit_slaves = $hit_masters = [];

        for ($i = $nodesCount * $retryPerNode; $i-- > 0;) {
            $db = $this->prepareMasterSlave($mastersCount, $slavesCount);
            $db->shuffleMasters = false;

            $hit_slaves[$db->getSlave()->getDsn()] = true;
            $hit_masters[$db->getMaster()->getDsn()] = true;

            if (\count($hit_slaves) === $slavesCount) {
                break;
            }
        }

        $this->assertCount(1, $hit_masters, 'same master hit');

        // slaves are always random
        $this->assertCount($slavesCount, $hit_slaves, 'all slaves hit');
    }

    public function testRestoreMasterAfterException(): void
    {
        $db = $this->prepareMasterSlave(1, 1);
        $this->assertTrue($db->enableSlaves);

        try {
            $db->useMaster(static function (Connection $db) {
                throw new \Exception('fail');
            });
            $this->fail('Exceptions was caught somewhere');
        } catch (\Exception $e) {
            // ok
        }

        $this->assertTrue($db->enableSlaves);
    }

    public function testExceptionContainsRawQuery(): void
    {
        $this->markTestSkipped('This test does not work on sqlite because preparing the failing query fails');
    }

    protected function prepareMasterSlave($masterCount, $slaveCount)
    {
        $db = $this->getConnection(true, true, true);

        for ($i = 0; $i < $masterCount; ++$i) {
            $this->prepareDatabase(true, true, [
                'dsn' => 'sqlite:' .  dirname(__DIR__) . "/data/yii_test_master{$i}.sq3",
            ]);
            $db->masters[] = [
                'dsn' => 'sqlite:' .  dirname(__DIR__) . "/data/yii_test_master{$i}.sq3",
            ];
        }

        for ($i = 0; $i < $slaveCount; ++$i) {
            $this->prepareDatabase(true, true, [
                'dsn' =>  'sqlite:' .  dirname(__DIR__) . "/data/yii_test_slave{$i}.sq3",
            ]);
            $db->slaves[] = [
                'dsn' => 'sqlite:' .  dirname(__DIR__) . "/data/yii_test_slave{$i}.sq3",
            ];
        }

        $db->close();

        return $db;
    }
}
