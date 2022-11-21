<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Cache\Dependency\TagDependency;
use Yiisoft\Db\Command\CommandInterface;
use Yiisoft\Db\Command\Param;
use Yiisoft\Db\Command\ParamInterface;
use Yiisoft\Db\Driver\PDO\ConnectionPDOInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidCallException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\InvalidParamException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Query\Data\DataReader;
use Yiisoft\Db\Schema\Schema;
use Yiisoft\Db\Schema\SchemaBuilderTrait;
use Yiisoft\Db\Tests\Support\Assert;
use Yiisoft\Db\Tests\Support\DbHelper;
use Yiisoft\Db\Tests\Support\TestTrait;

abstract class AbstractCommandTest extends TestCase
{
    use SchemaBuilderTrait;
    use TestTrait;

    protected ConnectionPDOInterface $db;
    protected string $upsertTestCharCast = '';

    public function testAddCheck(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $sql = $command->addCheck('name', 'table', 'id > 0')->getSql();


        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                ALTER TABLE [[table]] ADD CONSTRAINT [[name]] CHECK (id > 0)
                SQL,
                $db->getName(),
            ),
            $sql,
        );
    }

    public function testAddColumn(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $sql = $command->addColumn('table', 'column', Schema::TYPE_INTEGER)->getSql();

        $this->assertSame(
            <<<SQL
            ALTER TABLE `table` ADD `column` integer
            SQL,
            $sql,
        );
    }

    public function testAddCommentOnColumn(): void
    {
        $db = $this->getConnectionWithData();

        $command = $db->createCommand();
        $sql = $command->addCommentOnColumn('customer', 'id', 'Primary key.')->getSql();

        $this->assertStringContainsString(
            DbHelper::replaceQuotes(
                <<<SQL
                COMMENT ON COLUMN [[customer]].[[id]] IS 'Primary key.'
                SQL,
                $db->getName(),
            ),
            $sql,
        );
    }

    public function testAddCommentOnTable(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $sql = $command->addCommentOnTable('table', 'comment')->getSql();

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                COMMENT ON TABLE [[table]] IS 'comment'
                SQL,
                $db->getName(),
            ),
            $sql,
        );
    }

    public function testAddForeignKey(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $sql = $command->addForeignKey('name', 'table', 'column', 'ref_table', 'ref_column')->getSql();

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                ALTER TABLE [[table]] ADD CONSTRAINT [[name]] FOREIGN KEY ([[column]]) REFERENCES [[ref_table]] ([[ref_column]])
                SQL,
                $db->getName(),
            ),
            $sql,
        );

        $sql = $command->addForeignKey('name', 'table', 'column', 'ref_table', 'ref_column', 'CASCADE')->getSql();

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                ALTER TABLE [[table]] ADD CONSTRAINT [[name]] FOREIGN KEY ([[column]]) REFERENCES [[ref_table]] ([[ref_column]]) ON DELETE CASCADE
                SQL,
                $db->getName(),
            ),
            $sql,
        );

        $sql = $command
            ->addForeignKey('name', 'table', 'column', 'ref_table', 'ref_column', 'CASCADE', 'CASCADE')
            ->getSql();

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                ALTER TABLE [[table]] ADD CONSTRAINT [[name]] FOREIGN KEY ([[column]]) REFERENCES [[ref_table]] ([[ref_column]]) ON DELETE CASCADE ON UPDATE CASCADE
                SQL,
                $db->getName(),
            ),
            $sql,
        );
    }

    public function testAddPrimaryKey(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $sql = $command->addPrimaryKey('name', 'table', 'column')->getSql();


        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                ALTER TABLE [[table]] ADD CONSTRAINT [[name]] PRIMARY KEY ([[column]])
                SQL,
                $db->getName(),
            ),
            $sql,
        );

        $sql = $command->addPrimaryKey('name', 'table', ['column1', 'column2'])->getSql();


        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                ALTER TABLE [[table]] ADD CONSTRAINT [[name]] PRIMARY KEY ([[column1]], [[column2]])
                SQL,
                $db->getName(),
            ),
            $sql,
        );
    }

    public function testAddUnique(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $sql = $command->addUnique('name', 'table', 'column')->getSql();

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                ALTER TABLE [[table]] ADD CONSTRAINT [[name]] UNIQUE ([[column]])
                SQL,
                $db->getName(),
            ),
            $sql,
        );

        $sql = $command->addUnique('name', 'table', ['column1', 'column2'])->getSql();

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                ALTER TABLE [[table]] ADD CONSTRAINT [[name]] UNIQUE ([[column1]], [[column2]])
                SQL,
                $db->getName(),
            ),
            $sql,
        );
    }

    public function testAlterColumn(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $sql = $command->alterColumn('table', 'column', Schema::TYPE_INTEGER)->getSql();

        $this->assertSame(
            <<<SQL
            ALTER TABLE `table` CHANGE `column` `column` integer
            SQL,
            $sql,
        );
    }

    public function testBindValues(): void
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
        $this->assertSame($param, $bindedValues['param']);
        $this->assertNotEquals($param, $bindedValues['int']);

        /* Replace test */
        $command->bindValues(['int' => $param]);
        $bindedValues = $command->getParams(false);

        $this->assertIsArray($bindedValues);
        $this->assertContainsOnlyInstancesOf(ParamInterface::class, $bindedValues);
        $this->assertCount(3, $bindedValues);
        $this->assertSame($param, $bindedValues['int']);
    }

    public function testCache(): void
    {
        $db = $this->getConnection();

        $tagDependency = new TagDependency('tag');
        $command = $db->createCommand();
        $command->cache(100, $tagDependency);

        $this->assertInstanceOf(CommandInterface::class, $command);
        $this->assertSame(100, Assert::getInaccessibleProperty($command, 'queryCacheDuration'));
        $this->assertSame($tagDependency, Assert::getInaccessibleProperty($command, 'queryCacheDependency'));
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

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\CommandProvider::createIndex()
     */
    public function testCreateIndex(
        string $name,
        string $table,
        array|string $column,
        string $indexType,
        string $indexMethod,
        string $expected,
    ): void {
        $db = $this->getConnection();

        $command = $db->createCommand();

        $sql = $command->createIndex($name, $table, $column, $indexType, $indexMethod)->getSql();

        $this->assertSame($expected, $sql);
    }

    public function testCreateView(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();

        $sql = $command->createView(
            'view',
            <<<SQL
            SELECT * FROM table
            SQL,
        )->getSql();

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                CREATE VIEW [[view]] AS SELECT * FROM table
                SQL,
                $db->getName(),
            ),
            $sql,
        );
    }

    public function testDataReaderCreationException(): void
    {
        $db = $this->getConnection();

        $this->expectException(InvalidParamException::class);
        $this->expectExceptionMessage('The PDOStatement cannot be null.');

        $sql = 'SELECT * FROM {{customer}}';
        new DataReader($db->createCommand($sql));
    }

    public function testDelete(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $sql = $command->delete('table', ['column' => 'value'])->getSql();

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                DELETE FROM [[table]] WHERE [[column]]=:qp0
                SQL,
                $db->getName(),
            ),
            $sql,
        );
    }

    public function testDropCheck(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $sql = $command->dropCheck('name', 'table')->getSql();

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                ALTER TABLE [[table]] DROP CONSTRAINT [[name]]
                SQL,
                $db->getName(),
            ),
            $sql,
        );
    }

    public function testDropColumn(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $sql = $command->dropColumn('table', 'column')->getSql();

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                ALTER TABLE [[table]] DROP COLUMN [[column]]
                SQL,
                $db->getName(),
            ),
            $sql,
        );
    }

    public function testDropCommentFromColumn(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $sql = $command->dropCommentFromColumn('table', 'column')->getSql();

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                COMMENT ON COLUMN [[table]].[[column]] IS NULL
                SQL,
                $db->getName(),
            ),
            $sql,
        );
    }

    public function testDropCommentFromTable(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $sql = $command->dropCommentFromTable('table')->getSql();

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                COMMENT ON TABLE [[table]] IS NULL
                SQL,
                $db->getName(),
            ),
            $sql,
        );
    }

    public function testDropForeingKey(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $sql = $command->dropForeignKey('name', 'table')->getSql();

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                ALTER TABLE [[table]] DROP CONSTRAINT [[name]]
                SQL,
                $db->getName(),
            ),
            $sql,
        );
    }

    public function testDropIndex(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $sql = $command->dropIndex('name', 'table')->getSql();

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                DROP INDEX [[name]] ON [[table]]
                SQL,
                $db->getName(),
            ),
            $sql,
        );
    }

    public function testDropPrimaryKey(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $sql = $command->dropPrimaryKey('name', 'table')->getSql();

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                ALTER TABLE [[table]] DROP CONSTRAINT [[name]]
                SQL,
                $db->getName(),
            ),
            $sql,
        );
    }

    public function testDropTable(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $sql = $command->dropTable('table')->getSql();

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                DROP TABLE [[table]]
                SQL,
                $db->getName(),
            ),
            $sql,
        );
    }

    public function testDropView(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $sql = $command->dropView('view')->getSql();

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                DROP VIEW [[view]]
                SQL,
                $db->getName(),
            ),
            $sql,
        );
    }

    public function testDropUnique(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $sql = $command->dropUnique('name', 'table')->getSql();

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                ALTER TABLE [[table]] DROP CONSTRAINT [[name]]
                SQL,
                $db->getName(),
            ),
            $sql,
        );
    }

    public function testExecuteWithSqlEmtpy(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();

        $this->assertSame(0, $command->execute());
    }

    public function testGetParams(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $values = [
            'int' => 1,
            'string' => 'str',
        ];
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

    public function testLastInsertIdException(): void
    {
        $db = $this->getConnection();

        $db->close();

        $this->expectException(InvalidCallException::class);

        $db->getLastInsertID();
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
        $db = $this->getConnectionWithData();

        $command = $db->createCommand(
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

    public function testRenameColumn(): void
    {
        $db = $this->getConnection();

        $sql = $db->createCommand()->renameColumn('table', 'oldname', 'newname')->getSql();

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                ALTER TABLE [[table]] RENAME COLUMN [[oldname]] TO [[newname]]
                SQL,
                $db->getName(),
            ),
            $sql,
        );
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

    public function testSetRetryHandler(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();

        $handler = static fn (): bool => true;

        Assert::invokeMethod($command, 'setRetryHandler', [$handler]);

        $this->assertSame($handler, Assert::getInaccessibleProperty($command, 'retryHandler'));
    }

    public function testTruncateTable(): void
    {
        $db = $this->getConnectionWithData();

        $command = $db->createCommand();
        $sql = $command->truncateTable('table')->getSql();

        $this->assertSame(
            <<<SQL
            TRUNCATE TABLE `table`
            SQL,
            $sql,
        );
    }

    protected function performAndCompareUpsertResult(ConnectionPDOInterface $db, array $data): void
    {
        $params = $data['params'];
        $expected = $data['expected'] ?? $params[1];

        $command = $db->createCommand();
        call_user_func_array([$command, 'upsert'], $params);
        $command->execute();

        $actual = $this->getQuery($db)
            ->select(['email', 'address' => new Expression($this->upsertTestCharCast), 'status'])
            ->from('T_upsert')
            ->one();
        $this->assertEquals($expected, $actual, $this->upsertTestCharCast);
    }
}
