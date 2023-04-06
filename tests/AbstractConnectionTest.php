<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests;

use Exception;
use PHPUnit\Framework\TestCase;
use Throwable;
use Yiisoft\Db\Driver\Pdo\PdoConnectionInterface;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Profiler\Context\ConnectionContext;
use Yiisoft\Db\Profiler\ContextInterface;
use Yiisoft\Db\Query\BatchQueryResult;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Tests\Support\Assert;
use Yiisoft\Db\Tests\Support\TestTrait;
use Yiisoft\Db\Profiler\ProfilerInterface;

abstract class AbstractConnectionTest extends TestCase
{
    use TestTrait;

    /**
     * @throws Exception
     */
    public function testConnection(): void
    {
        $this->assertInstanceOf(PdoConnectionInterface::class, $this->getConnection());
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

    public function testGetDriverName(): void
    {
        $db = $this->getConnection();

        $this->assertSame($this->getDriverName(), $db->getDriverName());
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
            function (PdoConnectionInterface $db) {
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

    public function testProfiler(): void
    {
        $db = $this->getConnection();

        $profiler = new class ($this) implements ProfilerInterface {
            public function __construct(private TestCase $test)
            {
            }

            public function begin(string $token, ContextInterface|array $context = []): void
            {
                $this->test->assertInstanceOf(ConnectionContext::class, $context);
                $this->test->assertSame('connection', $context->getType());
                $this->test->assertIsArray($context->asArray());
            }

            public function end(string $token, ContextInterface|array $context = []): void
            {
                $this->test->assertInstanceOf(ConnectionContext::class, $context);
                $this->test->assertSame('connection', $context->getType());
                $this->test->assertIsArray($context->asArray());
            }
        };
        $db->setProfiler($profiler);
        $db->open();
    }

    public function testSetTablePrefix(): void
    {
        $db = $this->getConnection();

        $db->setTablePrefix('pre_');

        $this->assertSame('pre_', $db->getTablePrefix());
    }

    public function testSerialized()
    {
        $connection = $this->getConnection();
        $connection->open();
        $serialized = serialize($connection);
        $this->assertNotNull($connection->getPDO());

        $unserialized = unserialize($serialized);
        $this->assertInstanceOf(PdoConnectionInterface::class, $unserialized);
        $this->assertNull($unserialized->getPDO());
        $this->assertEquals(123, $unserialized->createCommand('SELECT 123')->queryScalar());
        $this->assertNotNull($connection->getPDO());
    }

    private function getProfiler(): ProfilerInterface
    {
        return $this->createMock(ProfilerInterface::class);
    }
}
