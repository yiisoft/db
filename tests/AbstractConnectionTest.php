<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Driver\PDO\ConnectionPDOInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Query\BatchQueryResult;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Tests\Support\Assert;
use Yiisoft\Db\Tests\Support\TestTrait;

abstract class AbstractConnectionTest extends TestCase
{
    use TestTrait;

    public function testCacheKey(): void
    {
        $db = $this->getConnection();

        $driver = $db->getDriver();

        $this->assertEquals([$driver->getDsn(), $driver->getUsername()], $db->getCacheKey());
    }

    /**
     * @throws \Exception
     */
    public function testConnection(): void
    {
        $this->assertInstanceOf(ConnectionPDOInterface::class, $this->getConnection());
    }

    public function testCreateBatchQueryResult(): void
    {
        $db = $this->getConnection();

        $query = (new Query($db))->from('customer');

        $this->assertInstanceOf(BatchQueryResult::class, $db->createBatchQueryResult($query));
    }

    public function testCreateCommand(): void
    {
        $db = $this->getConnection();

        $sql = <<<SQL
        SELECT * FROM customer
        SQL;

        $params = ['id' => 1];
        $command = $db->createCommand($sql, $params);

        $this->assertSame($sql, $command->getSql());
        $this->assertSame($params, $command->getParams());
    }

    public function testGetName(): void
    {
        $db = $this->getConnection();

        $this->assertSame('sqlite', $db->getName());
    }

    public function testNotProfiler(): void
    {
        $db = $this->getConnection();

        $profiler = $this->getProfiler();

        $this->assertNull(Assert::getInaccessibleProperty($db, 'profiler'));

        $db->setProfiler($profiler);

        $this->assertSame($profiler, Assert::getInaccessibleProperty($db, 'profiler'));
    }

    public function testSetTablePrefix(): void
    {
        $db = $this->getConnection();

        $db->setTablePrefix('pre_');

        $this->assertSame('pre_', $db->getTablePrefix());
    }

    public function testTransactionShortcutException(): void
    {
        $db = $this->getConnectionWithData();

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
    }
}
