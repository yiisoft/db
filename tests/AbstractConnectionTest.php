<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests;

use Exception;
use PHPUnit\Framework\TestCase;
use Throwable;
use Yiisoft\Db\Driver\PDO\ConnectionPDOInterface;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Query\BatchQueryResult;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Tests\Support\Assert;
use Yiisoft\Db\Tests\Support\TestTrait;
use Yiisoft\Db\Profiler\ProfilerInterface;

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
     * @throws Exception
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

    /**
     * @throws InvalidConfigException
     * @throws \Yiisoft\Db\Exception\Exception
     */
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

        $this->assertSame($db->getName(), $db->getDriver()->getDriverName());
    }

    /**
     * @throws Throwable
     */
    public function testNestedTransactionNotSupported(): void
    {
        $db = $this->getConnection();

        $db->setEnableSavepoint(false);

        $this->assertFalse($db->isSavepointEnabled());

        $db->transaction(
            function (ConnectionPDOInterface $db) {
                $this->assertNotNull($db->getTransaction());
                $this->expectException(NotSupportedException::class);

                $db->beginTransaction();
            }
        );
    }

    public function testNotProfiler(): void
    {
        $db = $this->getConnection();

        $profiler = $this->getProfiler();

        $this->assertNull(Assert::getInaccessibleProperty($db, 'profiler'));

        $db->setProfiler($profiler);

        $this->assertSame($profiler, Assert::getInaccessibleProperty($db, 'profiler'));

        $db->setProfiler(null);

        $this->assertNull(Assert::getInaccessibleProperty($db, 'profiler'));
    }

    public function testSetTablePrefix(): void
    {
        $db = $this->getConnection();

        $db->setTablePrefix('pre_');

        $this->assertSame('pre_', $db->getTablePrefix());
    }

    private function getProfiler(): ProfilerInterface
    {
        return $this->createMock(ProfilerInterface::class);
    }
}
