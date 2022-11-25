<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Command\CommandInterface;
use Yiisoft\Db\Command\Param;
use Yiisoft\Db\Command\ParamInterface;
use Yiisoft\Db\Driver\PDO\CommandPDOInterface;
use Yiisoft\Db\Driver\PDO\ConnectionPDOInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidCallException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\InvalidParamException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Query\Data\DataReader;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Schema\Schema;
use Yiisoft\Db\Schema\SchemaBuilderTrait;
use Yiisoft\Db\Tests\Support\Assert;
use Yiisoft\Db\Tests\Support\DbHelper;
use Yiisoft\Db\Tests\Support\TestTrait;

use function call_user_func_array;

abstract class AbstractCommandTest extends TestCase
{
    use SchemaBuilderTrait;
    use TestTrait;

    protected ConnectionPDOInterface $db;
    protected string $upsertTestCharCast = '';

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
                $db->getName(),
            ),
            $command->getSql(),
        );
    }

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
     * @dataProvider \Yiisoft\Db\Tests\Provider\CommandProvider::rawSql()
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     *
     * {@see https://github.com/yiisoft/yii2/issues/8592}
     */
    public function testGetRawSql(string $sql, array $params, string $expectedRawSql): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand($sql, $params);

        $this->assertSame($expectedRawSql, $command->getRawSql());
    }

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

    public function testNoCache(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand()->noCache();

        $this->assertSame(-1, Assert::getInaccessibleProperty($command, 'queryCacheDuration'));
        $this->assertInstanceOf(CommandInterface::class, $command);
    }

    public function testPrepareCancel(): void
    {
        $db = $this->getConnection('customer');

        $command = $db->createCommand();
        $command->setSql(
            <<<SQL
            SELECT * FROM {{customer}}
            SQL
        );

        $this->assertNull($command->getPdoStatement());

        $command->prepare();

        $this->assertNotNull($command->getPdoStatement());

        $command->cancel();

        $this->assertNull($command->getPdoStatement());
    }

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
}
