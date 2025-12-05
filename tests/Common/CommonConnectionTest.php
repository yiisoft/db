<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Common;

use PHPUnit\Framework\TestCase;
use Throwable;
use Yiisoft\Db\Driver\Pdo\PdoConnectionInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Profiler\Context\ConnectionContext;
use Yiisoft\Db\Profiler\ContextInterface;
use Yiisoft\Db\Profiler\ProfilerInterface;
use Yiisoft\Db\Query\BatchQueryResult;
use Yiisoft\Db\Schema\Column\ColumnBuilder;
use Yiisoft\Db\Tests\Support\Assert;
use Yiisoft\Db\Tests\Support\IntegrationTestCase;
use Yiisoft\Db\Tests\Support\Stub\StubColumnFactory;

abstract class CommonConnectionTest extends IntegrationTestCase
{
    public function testCreateBatchQueryResult(): void
    {
        $db = $this->getSharedConnection();

        $query = $db->createQuery()->from('customer');

        $this->assertInstanceOf(BatchQueryResult::class, $db->createBatchQueryResult($query));
    }

    public function testCreateCommand(): void
    {
        $db = $this->getSharedConnection();

        $sql = <<<SQL
        SELECT * FROM customer
        SQL;

        $params = ['id' => 1];
        $command = $db->createCommand($sql, $params);

        $this->assertSame($sql, $command->getSql());
        $this->assertSame($params, $command->getParams());
    }

    public function testNestedTransactionNotSupported(): void
    {
        $db = $this->createConnection();
        $db->setEnableSavepoint(false);

        $this->assertFalse($db->isSavepointEnabled());

        $db->transaction(
            function (PdoConnectionInterface $db) {
                $this->assertNotNull($db->getTransaction());
                $this->expectException(NotSupportedException::class);

                $db->beginTransaction();
            },
        );

        $db->close();
    }

    public function testNotProfiler(): void
    {
        $db = $this->createConnection();

        $profiler = $this->createMock(ProfilerInterface::class);

        $this->assertNull(Assert::getPropertyValue($db, 'profiler'));

        $db->setProfiler($profiler);

        $this->assertSame($profiler, Assert::getPropertyValue($db, 'profiler'));

        $db->setProfiler(null);

        $this->assertNull(Assert::getPropertyValue($db, 'profiler'));

        $db->close();
    }

    public function testProfiler(): void
    {
        $db = $this->createConnection();

        $profiler = new class ($this) implements ProfilerInterface {
            public function __construct(private TestCase $test) {}

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
        $db->close();
    }

    public function testSetTablePrefix(): void
    {
        $db = $this->createConnection();
        $db->setTablePrefix('pre_');

        $this->assertSame('pre_', $db->getTablePrefix());

        $db->close();
    }

    public function testSerialized(): void
    {
        $db = $this->createConnection();
        $db->open();
        $serialized = serialize($db);
        $this->assertNotNull($db->getPdo());

        $unserialized = unserialize($serialized);
        $this->assertInstanceOf(PdoConnectionInterface::class, $unserialized);
        $this->assertNull($unserialized->getPdo());
        $this->assertEquals(123, $unserialized->createCommand('SELECT 123')->queryScalar());
        $this->assertNotNull($db->getPdo());

        $db->close();
    }

    public function getColumnBuilderClass(): void
    {
        $db = $this->getSharedConnection();

        $this->assertSame(ColumnBuilder::class, $db->getColumnBuilderClass());
    }

    public function testGetColumnFactory(): void
    {
        $db = $this->getSharedConnection();

        $this->assertInstanceOf(StubColumnFactory::class, $db->getColumnFactory());
    }

    public function testTransactionShortcutException(): void
    {
        $db = $this->createConnection();
        $this->loadFixture(db: $db);

        $callable = static function () use ($db) {
            $db->createCommand()->insert('profile', ['description' => 'test transaction shortcut'])->execute();
            throw new Exception('Exception in transaction shortcut');
        };

        $exception = null;
        try {
            $db->transaction($callable);
        } catch (Throwable $exception) {
        }

        $this->assertInstanceOf(Exception::class, $exception);

        $db->close();
    }
}
