<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Command;

use PHPUnit\Framework\Attributes\DataProviderExternal;
use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Constant\PseudoType;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Schema\Column\ColumnBuilder;
use Yiisoft\Db\Schema\Column\ColumnInterface;
use Yiisoft\Db\Schema\Column\IntegerColumn;
use Yiisoft\Db\Tests\Provider\CommandProvider;
use Yiisoft\Db\Tests\Support\Assert;
use Yiisoft\Db\Tests\Support\BaseTestCase;
use Yiisoft\Db\Tests\Support\TestHelper;

/**
 * @group db
 */
final class CommandTest extends BaseTestCase
{
    public function testAddCheck(): void
    {
        $db = TestHelper::createSqliteMemoryConnection();

        $command = $db->createCommand();
        $sql = $command->addCheck('table', 'name', 'id > 0')->getSql();


        $this->assertSame(
            $this->replaceQuotes(
                <<<SQL
                ALTER TABLE [[table]] ADD CONSTRAINT [[name]] CHECK (id > 0)
                SQL,
            ),
            $sql,
        );
    }

    #[DataProviderExternal(CommandProvider::class, 'columnTypes')]
    public function testAddColumn(ColumnInterface|string $type): void
    {
        $db = TestHelper::createSqliteMemoryConnection();

        $command = $db->createCommand();
        $sql = $command->addColumn('table', 'column', $type)->getSql();

        $columnType = $db->getQueryBuilder()->buildColumnDefinition($type);

        $this->assertSame(
            $this->replaceQuotes(
                <<<SQL
                ALTER TABLE [[table]] ADD [[column]] {$columnType}
                SQL,
            ),
            $sql,
        );
    }

    public function testAddCommentOnColumn(): void
    {
        $db = TestHelper::createSqliteMemoryConnection();

        $command = $db->createCommand();
        $sql = $command->addCommentOnColumn('customer', 'id', 'Primary key.')->getSql();

        $this->assertStringContainsString(
            $this->replaceQuotes(
                <<<SQL
                COMMENT ON COLUMN [[customer]].[[id]] IS 'Primary key.'
                SQL,
            ),
            $sql,
        );
    }

    public function testAddCommentOnTable(): void
    {
        $db = TestHelper::createSqliteMemoryConnection();

        $command = $db->createCommand();
        $sql = $command->addCommentOnTable('table', 'comment')->getSql();

        $this->assertSame(
            $this->replaceQuotes(
                <<<SQL
                COMMENT ON TABLE [[table]] IS 'comment'
                SQL,
            ),
            $sql,
        );
    }

    public function testAddDefaultValue(): void
    {
        $db = TestHelper::createSqliteMemoryConnection();

        $command = $db->createCommand();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\QueryBuilder\AbstractDDLQueryBuilder::addDefaultValue is not supported by this DBMS.',
        );

        $command->addDefaultValue('table', 'name', 'column', 'value');
    }

    #[DataProviderExternal(CommandProvider::class, 'addForeignKeySql')]
    public function testAddForeignKeySql(
        array|string $columns,
        array|string $referenceColumns,
        ?string $delete,
        ?string $update,
        string $expected,
    ): void {
        $db = TestHelper::createSqliteMemoryConnection();
        $command = $db->createCommand();

        $name = '{{fk_constraint}}';
        $tableName = '{{fk_table}}';
        $referenceTable = '{{fk_referenced_table}}';

        $sql = $command->addForeignKey($tableName, $name, $columns, $referenceTable, $referenceColumns, $delete, $update)->getSql();

        $this->assertSame(
            $this->replaceQuotes($expected),
            $sql,
        );
    }

    #[DataProviderExternal(CommandProvider::class, 'addPrimaryKeySql')]
    public function testAddPrimaryKeySql(string $name, string $tableName, array|string $column, string $expected): void
    {
        $db = TestHelper::createSqliteMemoryConnection();

        $command = $db->createCommand();
        $sql = $command->addPrimaryKey($tableName, $name, $column)->getSql();

        $this->assertSame(
            $this->replaceQuotes($expected),
            $sql,
        );
    }

    #[DataProviderExternal(CommandProvider::class, 'addUniqueSql')]
    public function testAddUniqueSql(string $name, string $tableName, array|string $column, string $expected): void
    {
        $db = TestHelper::createSqliteMemoryConnection();

        $command = $db->createCommand();
        $sql = $command->addUnique($tableName, $name, $column)->getSql();

        $this->assertSame(
            $this->replaceQuotes($expected),
            $sql,
        );
    }

    public function testAlterColumn(): void
    {
        $db = TestHelper::createSqliteMemoryConnection();

        $command = $db->createCommand();
        $sql = $command->alterColumn('table', 'column', ColumnType::INTEGER)->getSql();

        $this->assertSame(
            $this->replaceQuotes(
                <<<SQL
                ALTER TABLE [[table]] CHANGE [[column]] [[column]] integer
                SQL,
            ),
            $sql,
        );
    }

    public function testBatchInsert(): void
    {
        $db = TestHelper::createSqliteMemoryConnection();

        $command = $db->createCommand();
        $command->insertBatch('table', [['value1', 'value2'], ['value3', 'value4']], ['column1', 'column2']);

        $this->assertSame('INSERT INTO [table] ([column1], [column2]) VALUES (:qp0, :qp1), (:qp2, :qp3)', $command->getSql());
        $this->assertSame(
            [
                ':qp0' => 'value1',
                ':qp1' => 'value2',
                ':qp2' => 'value3',
                ':qp3' => 'value4',
            ],
            $command->getParams(),
        );
    }

    #[DataProviderExternal(CommandProvider::class, 'createIndexSql')]
    public function testCreateIndexSql(
        string $name,
        string $table,
        array|string $column,
        string $indexType,
        string $indexMethod,
        string $expected,
    ): void {
        $db = TestHelper::createSqliteMemoryConnection();

        $command = $db->createCommand();

        $sql = $command->createIndex($table, $name, $column, $indexType, $indexMethod)->getSql();

        $this->assertSame(
            $this->replaceQuotes($expected),
            $sql,
        );
    }

    public function testCheckIntegrity(): void
    {
        $db = TestHelper::createSqliteMemoryConnection();

        $command = $db->createCommand();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\QueryBuilder\AbstractDDLQueryBuilder::checkIntegrity is not supported by this DBMS.',
        );

        $command->checkIntegrity('schema', 'table')->execute();
    }

    public function testCreateTable(): void
    {
        $db = TestHelper::createSqliteMemoryConnection();

        $command = $db->createCommand();

        $expected = <<<SQL
        CREATE TABLE [test_table] (
        \t[id] integer PRIMARY KEY AUTOINCREMENT,
        \t[name] varchar(255) NOT NULL,
        \t[email] varchar(255) NOT NULL,
        \t[address] varchar(255) NOT NULL,
        \t[status] integer NOT NULL,
        \t[profile_id] integer NOT NULL,
        \t[data] json CHECK (json_valid([data])),
        \t[created_at] timestamp NOT NULL,
        \t[updated_at] timestamp NOT NULL
        ) CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB
        SQL;
        $columns = [
            'id' => PseudoType::PK,
            'name' => ColumnType::STRING . '(255) NOT NULL',
            'email' => new Expression('varchar(255) NOT NULL'),
            'address' => ColumnBuilder::string()->notNull(),
            'status' => new IntegerColumn(notNull: true),
            'profile_id' => ColumnType::INTEGER . ' NOT NULL',
            'data' => ColumnBuilder::json(),
            'created_at' => ColumnType::TIMESTAMP . ' NOT NULL',
            'updated_at' => ColumnType::TIMESTAMP . ' NOT NULL',
        ];
        $options = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        $sql = $command->createTable('test_table', $columns, $options)->getSql();

        Assert::equalsWithoutLE($expected, $sql);
    }

    public function testCreateView(): void
    {
        $db = TestHelper::createSqliteMemoryConnection();

        $command = $db->createCommand();

        $sql = $command->createView(
            'view',
            <<<SQL
            SELECT * FROM [[table]]
            SQL,
        )->getSql();

        $this->assertSame(
            $this->replaceQuotes(
                <<<SQL
                CREATE VIEW [[view]] AS SELECT * FROM [[table]]
                SQL,
            ),
            $sql,
        );
    }

    public function testDelete(): void
    {
        $db = TestHelper::createSqliteMemoryConnection();

        $command = $db->createCommand();
        $sql = $command->delete('table', ['column' => 'value'])->getSql();

        $this->assertSame(
            $this->replaceQuotes(
                <<<SQL
                DELETE FROM [[table]] WHERE [[column]] = :qp0
                SQL,
            ),
            $sql,
        );
    }

    public function testDropCheck(): void
    {
        $db = TestHelper::createSqliteMemoryConnection();

        $command = $db->createCommand();
        $sql = $command->dropCheck('table', 'name')->getSql();

        $this->assertSame(
            $this->replaceQuotes(
                <<<SQL
                ALTER TABLE [[table]] DROP CONSTRAINT [[name]]
                SQL,
            ),
            $sql,
        );
    }

    public function testDropColumn(): void
    {
        $db = TestHelper::createSqliteMemoryConnection();

        $command = $db->createCommand();
        $sql = $command->dropColumn('table', 'column')->getSql();

        $this->assertSame(
            $this->replaceQuotes(
                <<<SQL
                ALTER TABLE [[table]] DROP COLUMN [[column]]
                SQL,
            ),
            $sql,
        );
    }

    public function testDropCommentFromColumn(): void
    {
        $db = TestHelper::createSqliteMemoryConnection();

        $command = $db->createCommand();
        $sql = $command->dropCommentFromColumn('table', 'column')->getSql();

        $this->assertSame(
            $this->replaceQuotes(
                <<<SQL
                COMMENT ON COLUMN [[table]].[[column]] IS NULL
                SQL,
            ),
            $sql,
        );
    }

    public function testDropCommentFromTable(): void
    {
        $db = TestHelper::createSqliteMemoryConnection();

        $command = $db->createCommand();
        $sql = $command->dropCommentFromTable('table')->getSql();

        $this->assertSame(
            $this->replaceQuotes(
                <<<SQL
                COMMENT ON TABLE [[table]] IS NULL
                SQL,
            ),
            $sql,
        );
    }

    public function testDropDefaultValue(): void
    {
        $db = TestHelper::createSqliteMemoryConnection();

        $command = $db->createCommand();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\QueryBuilder\AbstractDDLQueryBuilder::dropDefaultValue is not supported by this DBMS.',
        );

        $command->dropDefaultValue('table', 'column');
    }

    public function testDropForeingKey(): void
    {
        $db = TestHelper::createSqliteMemoryConnection();

        $command = $db->createCommand();
        $sql = $command->dropForeignKey('table', 'name')->getSql();

        $this->assertSame(
            $this->replaceQuotes(
                <<<SQL
                ALTER TABLE [[table]] DROP CONSTRAINT [[name]]
                SQL,
            ),
            $sql,
        );
    }

    public function testDropIndex(): void
    {
        $db = TestHelper::createSqliteMemoryConnection();

        $command = $db->createCommand();
        $sql = $command->dropIndex('table', 'name')->getSql();

        $this->assertSame(
            $this->replaceQuotes(
                <<<SQL
                DROP INDEX [[name]] ON [[table]]
                SQL,
            ),
            $sql,
        );
    }

    public function testDropPrimaryKey(): void
    {
        $db = TestHelper::createSqliteMemoryConnection();

        $command = $db->createCommand();
        $sql = $command->dropPrimaryKey('table', 'name')->getSql();

        $this->assertSame(
            $this->replaceQuotes(
                <<<SQL
                ALTER TABLE [[table]] DROP CONSTRAINT [[name]]
                SQL,
            ),
            $sql,
        );
    }

    public function testDropView(): void
    {
        $db = TestHelper::createSqliteMemoryConnection();

        $command = $db->createCommand();
        $sql = $command->dropView('view')->getSql();

        $this->assertSame(
            $this->replaceQuotes(
                <<<SQL
                DROP VIEW [[view]]
                SQL,
            ),
            $sql,
        );
    }

    #[DataProviderExternal(CommandProvider::class, 'dropTable')]
    public function testDropTable(string $expected, ?bool $ifExists, ?bool $cascade): void
    {
        $db = TestHelper::createSqliteMemoryConnection();
        $command = $db->createCommand();

        if ($ifExists === null && $cascade === null) {
            $command = $command->dropTable('table');
        } elseif ($ifExists === null) {
            $command = $command->dropTable('table', cascade: $cascade);
        } elseif ($cascade === null) {
            $command = $command->dropTable('table', ifExists: $ifExists);
        } else {
            $command = $command->dropTable('table', ifExists: $ifExists, cascade: $cascade);
        }

        $expectedSql = $this->replaceQuotes($expected);

        $this->assertSame($expectedSql, $command->getSql());
    }

    public function testDropUnique(): void
    {
        $db = TestHelper::createSqliteMemoryConnection();

        $command = $db->createCommand();
        $sql = $command->dropUnique('table', 'name')->getSql();

        $this->assertSame(
            $this->replaceQuotes(
                <<<SQL
                ALTER TABLE [[table]] DROP CONSTRAINT [[name]]
                SQL,
            ),
            $sql,
        );
    }

    public function testExecute(): void
    {
        $db = TestHelper::createSqliteMemoryConnection();

        $command = $db->createCommand();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Tests\Support\Stub\Command::internalExecute is not supported by this DBMS.',
        );

        $command->createTable('customer', ['id' => 'pk'])->execute();
    }

    public function testInsert(): void
    {
        $db = TestHelper::createSqliteMemoryConnection();

        $command = $db->createCommand();
        $command->insert('customer', ['email' => 't1@example.com', 'name' => 'test', 'address' => 'test address']);

        $this->assertSame(
            'INSERT INTO [customer] ([email], [name], [address]) VALUES (:qp0, :qp1, :qp2)',
            $command->getSql(),
        );
        $this->assertSame(
            [
                ':qp0' => 't1@example.com',
                ':qp1' => 'test',
                ':qp2' => 'test address',
            ],
            $command->getParams(),
        );
    }

    public function testRenameColumn(): void
    {
        $db = TestHelper::createSqliteMemoryConnection();

        $sql = $db->createCommand()->renameColumn('table', 'oldname', 'newname')->getSql();

        $this->assertSame(
            $this->replaceQuotes(
                <<<SQL
                ALTER TABLE [[table]] RENAME COLUMN [[oldname]] TO [[newname]]
                SQL,
            ),
            $sql,
        );
    }

    public function testRenameTable(): void
    {
        $db = TestHelper::createSqliteMemoryConnection();

        $sql = $db->createCommand()->renameTable('table', 'newname')->getSql();

        $this->assertSame(
            $this->replaceQuotes(
                <<<SQL
                RENAME TABLE [[table]] TO [[newname]]
                SQL,
            ),
            $sql,
        );
    }

    public function testResetSequence(): void
    {
        $db = TestHelper::createSqliteMemoryConnection();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\QueryBuilder\AbstractDMLQueryBuilder::resetSequence() is not supported by this DBMS.',
        );

        $db->createCommand()->resetSequence('table', 5);
    }

    public function testSetRetryHandler(): void
    {
        $db = TestHelper::createSqliteMemoryConnection();

        $command = $db->createCommand();
        $handler = static fn(): bool => true;
        $command->setRetryHandler($handler);

        $this->assertSame($handler, Assert::getPropertyValue($command, 'retryHandler'));
    }

    public function testTruncateTable(): void
    {
        $db = TestHelper::createSqliteMemoryConnection();

        $command = $db->createCommand();
        $sql = $command->truncateTable('{{table}}')->getSql();

        $this->assertSame(
            $this->replaceQuotes(
                <<<SQL
                TRUNCATE TABLE [[table]]
                SQL,
            ),
            $sql,
        );
    }

    public function testUpdate(): void
    {
        $db = TestHelper::createSqliteMemoryConnection();

        $command = $db->createCommand();
        $command->update('{{table}}', ['name' => 'John'], ['id' => 1]);

        $this->assertSame('UPDATE [table] SET [name]=:qp0 WHERE [id] = 1', $command->getSql());
        $this->assertSame([':qp0' => 'John'], $command->getParams());
    }

    public function testUpsert(): void
    {
        $db = TestHelper::createSqliteMemoryConnection();

        $command = $db->createCommand();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\QueryBuilder\AbstractDMLQueryBuilder::upsert is not supported by this DBMS.',
        );

        $command->upsert('{{table}}', []);
    }

    public function testWithDbTypecasting(): void
    {
        $db = TestHelper::createSqliteMemoryConnection();
        $command = $db->createCommand();

        $this->assertTrue(Assert::getPropertyValue($command, 'dbTypecasting'));

        $command = $command->withDbTypecasting(false);

        $this->assertFalse(Assert::getPropertyValue($command, 'dbTypecasting'));

        $command = $command->withDbTypecasting();

        $this->assertTrue(Assert::getPropertyValue($command, 'dbTypecasting'));
    }

    public function testWithPhpTypecasting(): void
    {
        $db = TestHelper::createSqliteMemoryConnection();
        $command = $db->createCommand();

        $this->assertFalse(Assert::getPropertyValue($command, 'phpTypecasting'));

        $command = $command->withPhpTypecasting();

        $this->assertTrue(Assert::getPropertyValue($command, 'phpTypecasting'));

        $command = $command->withPhpTypecasting(false);

        $this->assertFalse(Assert::getPropertyValue($command, 'phpTypecasting'));
    }

    public function testWithTypecasting(): void
    {
        $db = TestHelper::createSqliteMemoryConnection();
        $command = $db->createCommand();

        $this->assertTrue(Assert::getPropertyValue($command, 'dbTypecasting'));
        $this->assertFalse(Assert::getPropertyValue($command, 'phpTypecasting'));

        $command = $command->withTypecasting(false);

        $this->assertFalse(Assert::getPropertyValue($command, 'dbTypecasting'));
        $this->assertFalse(Assert::getPropertyValue($command, 'phpTypecasting'));

        $command = $command->withTypecasting();

        $this->assertTrue(Assert::getPropertyValue($command, 'dbTypecasting'));
        $this->assertTrue(Assert::getPropertyValue($command, 'phpTypecasting'));
    }
}
