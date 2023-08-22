<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Command;

use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Schema\SchemaInterface;
use Yiisoft\Db\Tests\AbstractCommandTest;
use Yiisoft\Db\Tests\Support\Assert;
use Yiisoft\Db\Tests\Support\DbHelper;
use Yiisoft\Db\Tests\Support\TestTrait;

/**
 * @group db
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class CommandTest extends AbstractCommandTest
{
    use TestTrait;

    public function testAddCheck(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $sql = $command->addCheck('table', 'name', 'id > 0')->getSql();


        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                ALTER TABLE [[table]] ADD CONSTRAINT [[name]] CHECK (id > 0)
                SQL,
                $db->getDriverName(),
            ),
            $sql,
        );
    }

    public function testAddColumn(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $sql = $command->addColumn('table', 'column', SchemaInterface::TYPE_INTEGER)->getSql();

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                ALTER TABLE [[table]] ADD [[column]] integer
                SQL,
                $db->getDriverName(),
            ),
            $sql,
        );
    }

    public function testAddCommentOnColumn(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $sql = $command->addCommentOnColumn('customer', 'id', 'Primary key.')->getSql();

        $this->assertStringContainsString(
            DbHelper::replaceQuotes(
                <<<SQL
                COMMENT ON COLUMN [[customer]].[[id]] IS 'Primary key.'
                SQL,
                $db->getDriverName(),
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
                $db->getDriverName(),
            ),
            $sql,
        );
    }

    public function testAddDefaultValue(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\QueryBuilder\AbstractDDLQueryBuilder::addDefaultValue is not supported by this DBMS.'
        );

        $command->addDefaultValue('table', 'name', 'column', 'value');
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\CommandProvider::addForeignKeySql
     */
    public function testAddForeignKeySql(
        string $name,
        string $tableName,
        array|string $column1,
        array|string $column2,
        string|null $delete,
        string|null $update,
        string $expected
    ): void {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $sql = $command->addForeignKey($tableName, $name, $column1, $tableName, $column2, $delete, $update)->getSql();

        $this->assertSame($expected, $sql);
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\CommandProvider::addPrimaryKeySql
     */
    public function testAddPrimaryKeySql(string $name, string $tableName, array|string $column, string $expected): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $sql = $command->addPrimaryKey($tableName, $name, $column)->getSql();


        $this->assertSame($expected, $sql);
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\CommandProvider::addUniqueSql
     */
    public function testAddUniqueSql(string $name, string $tableName, array|string $column, string $expected): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $sql = $command->addUnique($tableName, $name, $column)->getSql();

        $this->assertSame($expected, $sql);
    }

    public function testAlterColumn(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $sql = $command->alterColumn('table', 'column', SchemaInterface::TYPE_INTEGER)->getSql();

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                ALTER TABLE [[table]] CHANGE [[column]] [[column]] integer
                SQL,
                $db->getDriverName(),
            ),
            $sql,
        );
    }

    public function testBatchInsert(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Tests\Support\Stub\Schema::loadTableSchema is not supported by this DBMS.'
        );

        $command->batchInsert('table', ['column1', 'column2'], [['value1', 'value2'], ['value3', 'value4']]);
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\CommandProvider::createIndexSql
     */
    public function testCreateIndexSql(
        string $name,
        string $table,
        array|string $column,
        string $indexType,
        string $indexMethod,
        string $expected,
    ): void {
        $db = $this->getConnection();

        $command = $db->createCommand();

        $sql = $command->createIndex($table, $name, $column, $indexType, $indexMethod)->getSql();

        $this->assertSame($expected, $sql);
    }

    public function testCheckIntegrity(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\QueryBuilder\AbstractDDLQueryBuilder::checkIntegrity is not supported by this DBMS.'
        );

        $command->checkIntegrity('schema', 'table')->execute();
    }

    public function testCreateTable(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();

        $expected = <<<SQL
        CREATE TABLE [test_table] (
        \t[id] pk,
        \t[name] string(255) NOT NULL,
        \t[email] string(255) NOT NULL,
        \t[address] string(255) NOT NULL,
        \t[status] integer NOT NULL,
        \t[profile_id] integer NOT NULL,
        \t[created_at] timestamp NOT NULL,
        \t[updated_at] timestamp NOT NULL
        ) CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB
        SQL;
        $columns = [
            'id' => SchemaInterface::TYPE_PK,
            'name' => SchemaInterface::TYPE_STRING . '(255) NOT NULL',
            'email' => SchemaInterface::TYPE_STRING . '(255) NOT NULL',
            'address' => SchemaInterface::TYPE_STRING . '(255) NOT NULL',
            'status' => SchemaInterface::TYPE_INTEGER . ' NOT NULL',
            'profile_id' => SchemaInterface::TYPE_INTEGER . ' NOT NULL',
            'created_at' => SchemaInterface::TYPE_TIMESTAMP . ' NOT NULL',
            'updated_at' => SchemaInterface::TYPE_TIMESTAMP . ' NOT NULL',
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
            SELECT * FROM [[table]]
            SQL,
        )->getSql();

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                CREATE VIEW [[view]] AS SELECT * FROM [[table]]
                SQL,
                $db->getDriverName(),
            ),
            $sql,
        );
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
                $db->getDriverName(),
            ),
            $sql,
        );
    }

    public function testDropCheck(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $sql = $command->dropCheck('table', 'name')->getSql();

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                ALTER TABLE [[table]] DROP CONSTRAINT [[name]]
                SQL,
                $db->getDriverName(),
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
                $db->getDriverName(),
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
                $db->getDriverName(),
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
                $db->getDriverName(),
            ),
            $sql,
        );
    }

    public function testDropDefaultValue(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\QueryBuilder\AbstractDDLQueryBuilder::dropDefaultValue is not supported by this DBMS.'
        );

        $command->dropDefaultValue('table', 'column');
    }

    public function testDropForeingKey(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $sql = $command->dropForeignKey('table', 'name')->getSql();

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                ALTER TABLE [[table]] DROP CONSTRAINT [[name]]
                SQL,
                $db->getDriverName(),
            ),
            $sql,
        );
    }

    public function testDropIndex(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $sql = $command->dropIndex('table', 'name')->getSql();

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                DROP INDEX [[name]] ON [[table]]
                SQL,
                $db->getDriverName(),
            ),
            $sql,
        );
    }

    public function testDropPrimaryKey(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $sql = $command->dropPrimaryKey('table', 'name')->getSql();

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                ALTER TABLE [[table]] DROP CONSTRAINT [[name]]
                SQL,
                $db->getDriverName(),
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
                $db->getDriverName(),
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
                $db->getDriverName(),
            ),
            $sql,
        );
    }

    public function testDropUnique(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $sql = $command->dropUnique('table', 'name')->getSql();

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                ALTER TABLE [[table]] DROP CONSTRAINT [[name]]
                SQL,
                $db->getDriverName(),
            ),
            $sql,
        );
    }

    public function testExecute(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Tests\Support\Stub\Command::internalExecute is not supported by this DBMS.'
        );

        $command->createTable('customer', ['id' => 'pk'])->execute();
    }

    public function testInsert(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Tests\Support\Stub\Schema::loadTableSchema is not supported by this DBMS.'
        );

        $command->insert('customer', ['email' => 't1@example.com', 'name' => 'test', 'address' => 'test address']);
    }

    public function testQuery(): void
    {
        $db = $this->getConnection(true);

        $command = $db->createCommand();
        $command->setSql(
            <<<SQL
            SELECT * FROM [[customer]]
            SQL
        );

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Tests\Support\Stub\Command::internalExecute is not supported by this DBMS.'
        );

        $command->query();
    }

    public function testQueryAll(): void
    {
        $db = $this->getConnection(true);

        $command = $db->createCommand();
        $command->setSql(
            <<<SQL
            SELECT * FROM [[customer]]
            SQL,
        );

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Tests\Support\Stub\Command::internalExecute is not supported by this DBMS.'
        );

        $command->queryAll();
    }

    public function testQueryColumn(): void
    {
        $db = $this->getConnection(true);

        $command = $db->createCommand();
        $command->setSql(
            <<<SQL
            SELECT * FROM [[customer]]
            SQL
        );

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Tests\Support\Stub\Command::internalExecute is not supported by this DBMS.'
        );

        $command->queryColumn();
    }

    public function testQueryOne(): void
    {
        $db = $this->getConnection(true);

        $command = $db->createCommand();
        $sql = <<<SQL
        SELECT * FROM [[customer]] ORDER BY [[id]]
        SQL;

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Tests\Support\Stub\Command::internalExecute is not supported by this DBMS.'
        );

        $command->setSql($sql)->queryOne();
    }

    public function testQueryScalar(): void
    {
        $db = $this->getConnection(true);

        $command = $db->createCommand();
        $sql = <<<SQL
        SELECT * FROM [[customer]] ORDER BY [[id]]
        SQL;

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Tests\Support\Stub\Command::internalExecute is not supported by this DBMS.'
        );

        $this->assertEquals(1, $command->setSql($sql)->queryScalar());
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
                $db->getDriverName(),
            ),
            $sql,
        );
    }

    public function testRenameTable(): void
    {
        $db = $this->getConnection();

        $sql = $db->createCommand()->renameTable('table', 'newname')->getSql();

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                RENAME TABLE [[table]] TO [[newname]]
                SQL,
                $db->getDriverName(),
            ),
            $sql,
        );
    }

    public function testResetSequence(): void
    {
        $db = $this->getConnection();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\QueryBuilder\AbstractDMLQueryBuilder::resetSequence() is not supported by this DBMS.'
        );

        $db->createCommand()->resetSequence('table', 5);
    }

    public function testSetRetryHandler(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $handler = static fn (): bool => true;
        $command->setRetryHandler($handler);

        $this->assertSame($handler, Assert::getInaccessibleProperty($command, 'retryHandler'));
    }

    public function testTruncateTable(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $sql = $command->truncateTable('{{table}}')->getSql();

        $this->assertSame(
            DbHelper::replaceQuotes(
                <<<SQL
                TRUNCATE TABLE [[table]]
                SQL,
                $db->getDriverName(),
            ),
            $sql,
        );
    }

    public function testUpdate(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Tests\Support\Stub\Schema::loadTableSchema is not supported by this DBMS.'
        );

        $command->update('{{table}}', [], [], []);
    }

    public function testUpsert(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\QueryBuilder\AbstractDMLQueryBuilder::upsert is not supported by this DBMS.'
        );

        $command->upsert('{{table}}', []);
    }

    public function testProfiler(string $sql = null): void
    {
        $this->expectExceptionMessage(
            'Yiisoft\Db\Tests\Support\Stub\Command::internalExecute is not supported by this DBMS.'
        );
        parent::testProfiler();
    }

    public function testProfilerData(string $sql = null): void
    {
        $this->expectExceptionMessage(
            'Yiisoft\Db\Tests\Support\Stub\Command::internalExecute is not supported by this DBMS.'
        );
        parent::testProfilerData();
    }
}
