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
use Yiisoft\Db\QueryBuilder\QueryBuilder;
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
            <<<SQL
            ALTER TABLE `table` ADD CONSTRAINT `name` CHECK (id > 0)
            SQL,
            $sql
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
            $sql
        );
    }

    public function testAddCommentOnColumn(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $sql = $command->addCommentOnColumn('table', 'column', 'comment')->getSql();

        $this->assertSame(
            <<<SQL
            COMMENT ON COLUMN `table`.`column` IS 'comment'
            SQL,
            $sql
        );
    }

    public function testAddCommentOnTable(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $sql = $command->addCommentOnTable('table', 'comment')->getSql();

        $this->assertSame(
            <<<SQL
            COMMENT ON TABLE `table` IS 'comment'
            SQL,
            $sql
        );
    }

    public function testAddDefaultValue(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();

        $this->expectException(NotsupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Tests\Support\Stubs\DDLQueryBuilder does not support adding default value constraints.'
        );

        $command->addDefaultValue('name', 'table', 'column', 'value')->getSql();
    }

    public function testAddForeignKey(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $sql = $command->addForeignKey('name', 'table', 'column', 'ref_table', 'ref_column')->getSql();

        $this->assertSame(
            <<<SQL
            ALTER TABLE `table` ADD CONSTRAINT `name` FOREIGN KEY (`column`) REFERENCES `ref_table` (`ref_column`)
            SQL,
            $sql
        );

        $sql = $command->addForeignKey('name', 'table', 'column', 'ref_table', 'ref_column', 'CASCADE')->getSql();

        $this->assertSame(
            <<<SQL
            ALTER TABLE `table` ADD CONSTRAINT `name` FOREIGN KEY (`column`) REFERENCES `ref_table` (`ref_column`) ON DELETE CASCADE
            SQL,
            $sql
        );

        $sql = $command
            ->addForeignKey('name', 'table', 'column', 'ref_table', 'ref_column', 'CASCADE', 'CASCADE')
            ->getSql();

        $this->assertSame(
            <<<SQL
            ALTER TABLE `table` ADD CONSTRAINT `name` FOREIGN KEY (`column`) REFERENCES `ref_table` (`ref_column`) ON DELETE CASCADE ON UPDATE CASCADE
            SQL,
            $sql
        );
    }

    public function testAddPrimaryKey(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $sql = $command->addPrimaryKey('name', 'table', 'column')->getSql();

        $this->assertSame(
            <<<SQL
            ALTER TABLE `table` ADD CONSTRAINT `name` PRIMARY KEY (`column`)
            SQL,
            $sql
        );

        $sql = $command->addPrimaryKey('name', 'table', ['column1', 'column2'])->getSql();

        $this->assertSame(
            <<<SQL
            ALTER TABLE `table` ADD CONSTRAINT `name` PRIMARY KEY (`column1`, `column2`)
            SQL,
            $sql
        );
    }

    public function testAddUnique(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $sql = $command->addUnique('name', 'table', 'column')->getSql();

        $this->assertSame(
            <<<SQL
            ALTER TABLE `table` ADD CONSTRAINT `name` UNIQUE (`column`)
            SQL,
            $sql
        );

        $sql = $command->addUnique('name', 'table', ['column1', 'column2'])->getSql();

        $this->assertSame(
            <<<SQL
            ALTER TABLE `table` ADD CONSTRAINT `name` UNIQUE (`column1`, `column2`)
            SQL,
            $sql
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
            $sql
        );
    }

    public function testBatchInsert(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();

        $this->expectException(NotsupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Tests\Support\Stubs\Schema::loadTableSchema() is not supported by core-db.'
        );

        $command->batchInsert(
            'table',
            ['column1', 'column2'],
            [
                ['value1', 'value2'],
                ['value3', 'value4'],
            ]
        )->getSql();
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

    public function testCheckIntegrity(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();

        $this->expectException(NotsupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Tests\Support\Stubs\DDLQueryBuilder does not support enabling/disabling integrity check.'
        );

        $command->checkIntegrity('schema', 'table')->getSql();
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

    public function testCreateIndex(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();

        $sql = $command->createIndex('name', 'table', 'column')->getSql();

        $this->assertSame(
            <<<SQL
            CREATE INDEX `name` ON `table` (`column`)
            SQL,
            $sql
        );

        $sql = $command->createIndex('name', 'table', ['column1', 'column2'])->getSql();

        $this->assertSame(
            <<<SQL
            CREATE INDEX `name` ON `table` (`column1`, `column2`)
            SQL,
            $sql
        );

        $sql = $command->createIndex('name', 'table', ['column1', 'column2'], QueryBuilder::INDEX_UNIQUE)->getSql();

        $this->assertSame(
            <<<SQL
            CREATE UNIQUE INDEX `name` ON `table` (`column1`, `column2`)
            SQL,
            $sql
        );

        $sql = $command->createIndex('name', 'table', ['column1', 'column2'], 'FULLTEXT')->getSql();

        $this->assertSame(
            <<<SQL
            CREATE FULLTEXT INDEX `name` ON `table` (`column1`, `column2`)
            SQL,
            $sql
        );

        $sql = $command->createIndex('name', 'table', ['column1', 'column2'], 'SPATIAL')->getSql();

        $this->assertSame(
            <<<SQL
            CREATE SPATIAL INDEX `name` ON `table` (`column1`, `column2`)
            SQL,
            $sql
        );

        $sql = $command->createIndex('name', 'table', ['column1', 'column2'], 'BITMAP')->getSql();

        $this->assertSame(
            <<<SQL
            CREATE BITMAP INDEX `name` ON `table` (`column1`, `column2`)
            SQL,
            $sql
        );
    }

    public function testCreateTable(): void
    {
        $this->db = $this->getConnectionWithData();

        $command = $this->db->createCommand();

        $expected = DbHelper::replaceQuotes(
            <<<SQL
            CREATE TABLE [[test_table]] (
            \t[[id]] pk,
            \t[[name]] string(255) NOT NULL,
            \t[[email]] string(255) NOT NULL,
            \t[[address]] string(255) NOT NULL,
            \t[[status]] integer NOT NULL,
            \t[[profile_id]] integer NOT NULL,
            \t[[created_at]] timestamp NOT NULL,
            \t[[updated_at]] timestamp NOT NULL
            ) CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB
            SQL,
            $this->db->getName(),
        );
        $columns = [
            'id' => $this->primaryKey(5),
            'name' => $this->string(255)->notNull(),
            'email' => $this->string(255)->notNull(),
            'address' => $this->string(255)->notNull(),
            'status' => $this->integer()->notNull(),
            'profile_id' => $this->integer()->notNull(),
            'created_at' => $this->timestamp()->notNull(),
            'updated_at' => $this->timestamp()->notNull(),
        ];
        $options = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        $sql = $command->createTable('test_table', $columns, $options)->getSql();

        Assert::equalsWithoutLE($expected, $sql);
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
            <<<SQL
            CREATE VIEW `view` AS SELECT * FROM table
            SQL,
            $sql
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
            <<<SQL
            DELETE FROM `table` WHERE `column`=:qp0
            SQL,
            $sql
        );
    }

    public function testDropCheck(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $sql = $command->dropCheck('name', 'table')->getSql();

        $this->assertSame(
            <<<SQL
            ALTER TABLE `table` DROP CONSTRAINT `name`
            SQL,
            $sql
        );
    }

    public function testDropColumn(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $sql = $command->dropColumn('table', 'column')->getSql();

        $this->assertSame(
            <<<SQL
            ALTER TABLE `table` DROP COLUMN `column`
            SQL,
            $sql
        );
    }

    public function testDropCommentFromColumn(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $sql = $command->dropCommentFromColumn('table', 'column')->getSql();

        $this->assertSame(
            <<<SQL
            COMMENT ON COLUMN `table`.`column` IS NULL
            SQL,
            $sql
        );
    }

    public function testDropCommentFromTable(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $sql = $command->dropCommentFromTable('table')->getSql();

        $this->assertSame(
            <<<SQL
            COMMENT ON TABLE `table` IS NULL
            SQL,
            $sql
        );
    }

    public function testDropDefaultValue(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();

        $this->expectException(NotsupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Tests\Support\Stubs\DDLQueryBuilder does not support dropping default value constraints.'
        );

        $command->dropDefaultValue('table', 'column')->getSql();
    }

    public function testDropForeingKey(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $sql = $command->dropForeignKey('name', 'table')->getSql();

        $this->assertSame(
            <<<SQL
            ALTER TABLE `table` DROP CONSTRAINT `name`
            SQL,
            $sql
        );
    }

    public function testDropIndex(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $sql = $command->dropIndex('name', 'table')->getSql();

        $this->assertSame(
            <<<SQL
            DROP INDEX `name` ON `table`
            SQL,
            $sql
        );
    }

    public function testDropPrimaryKey(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $sql = $command->dropPrimaryKey('name', 'table')->getSql();

        $this->assertSame(
            <<<SQL
            ALTER TABLE `table` DROP CONSTRAINT `name`
            SQL,
            $sql
        );
    }

    public function testDropTable(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $sql = $command->dropTable('table')->getSql();

        $this->assertSame(
            <<<SQL
            DROP TABLE `table`
            SQL,
            $sql
        );
    }

    public function testDropView(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $sql = $command->dropView('view')->getSql();

        $this->assertSame(
            <<<SQL
            DROP VIEW `view`
            SQL,
            $sql
        );
    }

    public function testDropUnique(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $sql = $command->dropUnique('name', 'table')->getSql();

        $this->assertSame(
            <<<SQL
            ALTER TABLE `table` DROP CONSTRAINT `name`
            SQL,
            $sql
        );
    }

    public function testExecute(): void
    {
        $db = $this->getConnectionWithData();

        $command = $db->createCommand(
            <<<SQL
            SELECT * FROM {{customer}} WHERE id=:id
            SQL,
            [
                ':id' => 1,
            ]
        );

        $this->expectException(NotsupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Tests\Support\Stubs\Command does not support internalExecute() by core-db.'
        );

        $command->execute();
    }

    public function testExecuteResetSequence(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();

        $this->expectException(NotsupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Tests\Support\Stubs\DMLQueryBuilder does not support resetting sequence.'
        );

        $command->executeResetSequence('table')->getSql();
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

    public function testInsert(): void
    {
        $db = $this->getConnectionWithData();

        $this->expectException(NotsupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Tests\Support\Stubs\Schema::loadTableSchema() is not supported by core-db.'
        );

        $command = $db->createCommand();
        $command
            ->insert('{{customer}}', ['email' => 't1@example.com', 'name' => 'test', 'address' => 'test address'])
            ->execute();
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

    public function testQuery(): void
    {
        $db = $this->getConnectionWithData();

        $command = $db->createCommand(
            <<<SQL
            SELECT * FROM {{customer}} WHERE id=:id
            SQL,
            [
                ':id' => 1,
            ]
        );

        $this->expectException(NotsupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Tests\Support\Stubs\Command does not support internalExecute() by core-db.'
        );

        $command->query();
    }

    public function testQueryAll(): void
    {
        $db = $this->getConnectionWithData();

        $command = $db->createCommand(
            <<<SQL
            SELECT * FROM {{customer}} WHERE id=:id
            SQL,
            [
                ':id' => 1,
            ]
        );

        $this->expectException(NotsupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Tests\Support\Stubs\Command does not support internalExecute() by core-db.'
        );

        $command->queryAll();
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
            <<<SQL
            ALTER TABLE `table` RENAME COLUMN `oldname` TO `newname`
            SQL,
            $sql,
        );
    }

    public function testRenameTable(): void
    {
        $db = $this->getConnection();

        $sql = $db->createCommand()->renameTable('table', 'newname')->getSql();

        $this->assertSame(
            <<<SQL
            RENAME TABLE `table` TO `newname`
            SQL,
            $sql,
        );
    }

    public function testResetSequence(): void
    {
        $db = $this->getConnection();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Tests\Support\Stubs\DMLQueryBuilder does not support resetting sequence.'
        );

        $db->createCommand()->resetSequence('table', 5)->getSql();
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

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\CommandProvider::upsert()
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function testUpsert(array $firstData, array $secondData): void
    {
        $db = $this->getConnectionWithData();

        $command = $db->createCommand();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Tests\Support\Stubs\DMLQueryBuilder does not support upsert.'
        );

        $command->upsert('table', $firstData)->getSql();
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
