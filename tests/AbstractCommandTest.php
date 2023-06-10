<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests;

use PHPUnit\Framework\TestCase;
use Throwable;
use Yiisoft\Db\Command\Param;
use Yiisoft\Db\Command\ParamInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Profiler\Context\CommandContext;
use Yiisoft\Db\Profiler\ContextInterface;
use Yiisoft\Db\Profiler\ProfilerInterface;
use Yiisoft\Db\Tests\Support\DbHelper;
use Yiisoft\Db\Tests\Support\TestTrait;

/**
 * @psalm-suppress RedundantCondition
 */
abstract class AbstractCommandTest extends TestCase
{
    use TestTrait;

    protected string $upsertTestCharCast = '';

    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function testAutoQuoting(): void
    {
        $db = $this->getConnection();

        $sql = <<<SQL
        SELECT [[id]], [[t.name]] FROM {{customer}} t
        SQL;
        $command = $db->createCommand($sql);

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                SELECT [[id]], [[t]].[[name]] FROM [[customer]] t
                SQL,
                $db->getDriverName(),
            ),
            $command->getSql(),
        );
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function testConstruct(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();

        $this->assertEmpty($command->getSql());

        $sql = <<<SQL
        SELECT * FROM customer WHERE name=:name
        SQL;
        $command = $db->createCommand($sql, [':name' => 'John']);

        $this->assertSame($sql, $command->getSql());
        $this->assertSame([':name' => 'John'], $command->getParams());
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function testGetParams(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $values = ['int' => 1, 'string' => 'str'];
        $command->bindValues($values);
        $bindedValues = $command->getParams(false);

        $this->assertIsArray($bindedValues);
        $this->assertContainsOnlyInstancesOf(ParamInterface::class, $bindedValues);
        $this->assertCount(2, $bindedValues);

        $param = new Param('str', 99);
        $command->bindValues(['param' => $param]);
        $bindedValues = $command->getParams(false);

        $this->assertIsArray($bindedValues);
        $this->assertContainsOnlyInstancesOf(ParamInterface::class, $bindedValues);
        $this->assertCount(3, $bindedValues);
        $this->assertEquals($param, $bindedValues['param']);
        $this->assertNotEquals($param, $bindedValues['int']);

        /* Replace test */
        $command->bindValues(['int' => $param]);
        $bindedValues = $command->getParams(false);

        $this->assertIsArray($bindedValues);
        $this->assertContainsOnlyInstancesOf(ParamInterface::class, $bindedValues);
        $this->assertCount(3, $bindedValues);
        $this->assertEquals($param, $bindedValues['int']);
    }

    /**
     * Test command getRawSql.
     *
     * @dataProvider \Yiisoft\Db\Tests\Provider\CommandProvider::rawSql
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     * @throws \Exception
     *
     * {@see https://github.com/yiisoft/yii2/issues/8592}
     */
    public function testGetRawSql(string $sql, array $params, string $expectedRawSql): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand($sql, $params);

        $this->assertSame($expectedRawSql, $command->getRawSql());
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function testGetSetSql(): void
    {
        $db = $this->getConnection();

        $sql = <<<SQL
        SELECT * FROM customer
        SQL;
        $command = $db->createCommand($sql);
        $this->assertSame($sql, $command->getSql());

        $sql2 = <<<SQL
        SELECT * FROM order
        SQL;
        $command->setSql($sql2);
        $this->assertSame($sql2, $command->getSql());
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function testPrepareCancel(): void
    {
        $db = $this->getConnection(true);

        $command = $db->createCommand();
        $command->setSql(
            <<<SQL
            SELECT * FROM [[customer]]
            SQL
        );

        $this->assertNull($command->getPdoStatement());

        $command->prepare();

        $this->assertNotNull($command->getPdoStatement());

        $command->cancel();

        $this->assertNull($command->getPdoStatement());
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws \Exception
     */
    public function testSetRawSql(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $command->setRawSql(
            <<<SQL
            SELECT 123
            SQL
        );

        $this->assertSame('SELECT 123', $command->getRawSql());
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function testSetSql(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $command->setSql(
            <<<SQL
            SELECT 123
            SQL
        );

        $this->assertSame('SELECT 123', $command->getSql());
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testProfiler(string $sql = null): void
    {
        $sql ??= 'SELECT 123';

        $db = $this->getConnection();
        $db->open();

        $profiler = $this->createMock(ProfilerInterface::class);
        $profiler->expects(self::once())
            ->method('begin')
            ->with($sql)
        ;
        $profiler->expects(self::once())
            ->method('end')
            ->with($sql)
        ;
        $db->setProfiler($profiler);

        $db->createCommand($sql)->execute();
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testProfilerData(string $sql = null): void
    {
        $sql ??= 'SELECT 123';

        $db = $this->getConnection();
        $db->open();

        $profiler = new class ($this, $sql) implements ProfilerInterface {
            public function __construct(private TestCase $test, private string $sql)
            {
            }

            public function begin(string $token, ContextInterface|array $context = []): void
            {
                $this->test->assertSame($this->sql, $token);
                $this->test->assertInstanceOf(CommandContext::class, $context);
                $this->test->assertSame('command', $context->getType());
                $this->test->assertIsArray($context->asArray());
            }

            public function end(string $token, ContextInterface|array $context = []): void
            {
                $this->test->assertSame($this->sql, $token);
                $this->test->assertInstanceOf(CommandContext::class, $context);
                $this->test->assertSame('command', $context->getType());
                $this->test->assertIsArray($context->asArray());
            }
        };

        $db->setProfiler($profiler);

        $db->createCommand($sql)->execute();
    }
}
