<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Schema;

use ReflectionException;
use Yiisoft\Db\Constraint\CheckConstraint;
use Yiisoft\Db\Constraint\Constraint;
use Yiisoft\Db\Constraint\DefaultValueConstraint;
use Yiisoft\Db\Constraint\ForeignKeyConstraint;
use Yiisoft\Db\Constraint\IndexConstraint;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Schema\TableSchemaInterface;
use Yiisoft\Db\Tests\AbstractSchemaTest;
use Yiisoft\Db\Tests\Support\Assert;
use Yiisoft\Db\Tests\Support\DbHelper;
use Yiisoft\Db\Tests\Support\Stub\ColumnSchema;
use Yiisoft\Db\Tests\Support\Stub\Schema;
use Yiisoft\Db\Tests\Support\Stub\TableSchema;
use Yiisoft\Db\Tests\Support\TestTrait;

/**
 * @group db
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class SchemaTest extends AbstractSchemaTest
{
    use TestTrait;

    /**
     * @throws ReflectionException
     */
    public function testFindTableNames(): void
    {
        $db = $this->getConnection();

        $schema = $db->getSchema();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage('Yiisoft\Db\Tests\Support\Stub\Schema does not support fetching all table names.');

        Assert::invokeMethod($schema, 'findTableNames', ['dbo']);
    }

    /**
     * @throws ReflectionException
     */
    public function testFindViewNames(): void
    {
        $db = $this->getConnection();

        $schema = $db->getSchema();

        $this->assertSame([], Assert::invokeMethod($schema, 'findViewNames', ['dbo']));
    }

    /**
     * @throws ReflectionException
     */
    public function testGetColumnPhpType(): void
    {
        $db = $this->getConnection();

        $schema = $db->getSchema();

        $columnBigInt = new ColumnSchema('bigint');
        $columnBigInt->type('bigint');

        $columnBoolean = new ColumnSchema('boolean');
        $columnBoolean->type('boolean');

        $columnInteger = new ColumnSchema('integer');
        $columnInteger->type('integer');

        $columnString = new ColumnSchema('string');
        $columnString->type('string');

        $this->assertSame(
            'integer',
            Assert::invokeMethod($schema, 'getColumnPhpType', [$columnBigInt]),
        );
        $this->assertSame(
            'boolean',
            Assert::invokeMethod($schema, 'getColumnPhpType', [$columnBoolean]),
        );
        $this->assertSame(
            'integer',
            Assert::invokeMethod($schema, 'getColumnPhpType', [$columnInteger]),
        );
        $this->assertSame(
            'string',
            Assert::invokeMethod($schema, 'getColumnPhpType', [$columnString]),
        );
    }

    /**
     * @throws NotSupportedException
     */
    public function testGetSchemaChecks(): void
    {
        $db = $this->getConnection();

        $checkConstraint = [
            (new CheckConstraint())
                ->columnNames(['col1', 'col2'])
                ->expression('col1 > col2')
                ->name('check_1'),
        ];
        $schemaMock = $this->getMockBuilder(Schema::class)
            ->onlyMethods(['findTableNames', 'loadTableChecks'])
            ->setConstructorArgs([$db, DbHelper::getSchemaCache()])
            ->getMock();
        $schemaMock->expects($this->once())->method('findTableNames')->willReturn(['T_constraints_1']);
        $schemaMock->expects($this->once())->method('loadTableChecks')->willReturn($checkConstraint);
        $tableChecks = $schemaMock->getSchemaChecks();

        $this->assertIsArray($tableChecks);

        foreach ($tableChecks as $checks) {
            $this->assertIsArray($checks);
            $this->assertContainsOnlyInstancesOf(CheckConstraint::class, $checks);
        }
    }

    /**
     * @throws NotSupportedException
     */
    public function testGetSchemaDefaultValues(): void
    {
        $db = $this->getConnection();

        $defaultValuesConstraint = [
            (new DefaultValueConstraint())
                ->columnNames(['C_default'])
                ->name('DF__T_constra__C_def__6203C3C6')
                ->value('((0))'),
        ];
        $schemaMock = $this->getMockBuilder(Schema::class)
            ->onlyMethods(['findTableNames', 'loadTableDefaultValues'])
            ->setConstructorArgs([$db, DbHelper::getSchemaCache()])
            ->getMock();
        $schemaMock->expects($this->once())->method('findTableNames')->willReturn(['T_constraints_1']);
        $schemaMock->expects($this->once())->method('loadTableDefaultValues')->willReturn($defaultValuesConstraint);
        $tableDefaultValues = $schemaMock->getSchemaDefaultValues();

        $this->assertIsArray($tableDefaultValues);

        foreach ($tableDefaultValues as $defaultValues) {
            $this->assertIsArray($defaultValues);
            $this->assertContainsOnlyInstancesOf(DefaultValueConstraint::class, $defaultValues);
        }
    }

    /**
     * @throws NotSupportedException
     */
    public function testGetSchemaForeignKeys(): void
    {
        $db = $this->getConnection();

        $foreingKeysConstraint = [
            (new ForeignKeyConstraint())
                ->name('CN_constraints_3')
                ->columnNames(['C_fk_id_1, C_fk_id_2'])
                ->foreignTableName('T_constraints_2')
                ->foreignColumnNames(['C_id_1', 'C_id_2']),
        ];
        $schemaMock = $this->getMockBuilder(Schema::class)
            ->onlyMethods(['findTableNames', 'loadTableForeignKeys'])
            ->setConstructorArgs([$db, DbHelper::getSchemaCache()])
            ->getMock();
        $schemaMock->expects($this->once())->method('findTableNames')->willReturn(['T_constraints_1']);
        $schemaMock->expects($this->once())->method('loadTableForeignKeys')->willReturn($foreingKeysConstraint);
        $tableForeignKeys = $schemaMock->getSchemaForeignKeys();

        $this->assertIsArray($tableForeignKeys);

        foreach ($tableForeignKeys as $foreignKeys) {
            $this->assertIsArray($foreignKeys);
            $this->assertContainsOnlyInstancesOf(ForeignKeyConstraint::class, $foreignKeys);
        }
    }

    /**
     * @throws NotSupportedException
     */
    public function testGetSchemaIndexes(): void
    {
        $db = $this->getConnection();

        $indexConstraint = [
            (new IndexConstraint())
                ->name('PK__T_constr__A9FAE80AC2B18E65')
                ->columnNames(['"C_id'])
                ->unique(true)
                ->primary(true),
        ];
        $schemaMock = $this->getMockBuilder(Schema::class)
            ->onlyMethods(['findTableNames', 'loadTableIndexes'])
            ->setConstructorArgs([$db, DbHelper::getSchemaCache()])
            ->getMock();
        $schemaMock->expects($this->once())->method('findTableNames')->willReturn(['T_constraints_1']);
        $schemaMock->expects($this->once())->method('loadTableIndexes')->willReturn($indexConstraint);
        $tableIndexes = $schemaMock->getSchemaIndexes();

        $this->assertIsArray($tableIndexes);

        foreach ($tableIndexes as $indexes) {
            $this->assertIsArray($indexes);
            $this->assertContainsOnlyInstancesOf(IndexConstraint::class, $indexes);
        }
    }

    public function testGetSchemaNames(): void
    {
        $db = $this->getConnection();

        $schema = $db->getSchema();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Tests\Support\Stub\Schema does not support fetching all schema names.'
        );

        $schema->getSchemaNames();
    }

    /**
     * @throws NotSupportedException
     * @throws ReflectionException
     */
    public function testGetSchemaNamesWithSchema(): void
    {
        $db = $this->getConnection();

        $schema = $db->getSchema();
        Assert::setInaccessibleProperty($schema, 'schemaNames', ['dbo', 'public']);

        $this->assertSame(['dbo', 'public'], $schema->getSchemaNames());
    }

    /**
     * @throws NotSupportedException
     */
    public function testGetSchemaPrimaryKeys(): void
    {
        $db = $this->getConnection();

        $pksConstraint = (new Constraint())->name('PK__T_constr__A9FAE80AC2B18E65')->columnNames(['"C_id']);
        $schemaMock = $this->getMockBuilder(Schema::class)
            ->onlyMethods(['findTableNames', 'loadTablePrimaryKey'])
            ->setConstructorArgs([$db, DbHelper::getSchemaCache()])
            ->getMock();
        $schemaMock->expects($this->once())->method('findTableNames')->willReturn(['T_constraints_1']);
        $schemaMock->expects($this->once())->method('loadTablePrimaryKey')->willReturn($pksConstraint);
        $tablePks = $schemaMock->getSchemaPrimaryKeys();

        $this->assertIsArray($tablePks);
        $this->assertContainsOnlyInstancesOf(Constraint::class, $tablePks);
    }

    /**
     * @throws NotSupportedException
     */
    public function testGetSchemaUniques(): void
    {
        $db = $this->getConnection();

        $uniquesConstraint = [(new Constraint())->name('CN_unique')->columnNames(['C_unique'])];
        $schemaMock = $this->getMockBuilder(Schema::class)
            ->onlyMethods(['findTableNames', 'loadTableUniques'])
            ->setConstructorArgs([$db, DbHelper::getSchemaCache()])
            ->getMock();
        $schemaMock->expects($this->once())->method('findTableNames')->willReturn(['T_constraints_1']);
        $schemaMock->expects($this->once())->method('loadTableUniques')->willReturn($uniquesConstraint);
        $tableUniques = $schemaMock->getSchemaUniques();

        $this->assertIsArray($tableUniques);

        foreach ($tableUniques as $uniques) {
            $this->assertIsArray($uniques);
            $this->assertContainsOnlyInstancesOf(Constraint::class, $uniques);
        }
    }

    public function getTableSchema(): void
    {
        $db = $this->getConnection();

        $schemaMock = $this->getMockBuilder(Schema::class)
            ->onlyMethods(['findTableNames', 'loadTableSchema'])
            ->setConstructorArgs([$db, DbHelper::getSchemaCache()])
            ->getMock();
        $schemaMock->expects($this->once())->method('findTableNames')->willReturn(['T_constraints_1']);
        $schemaMock->expects($this->once())->method('loadTableSchema')->willReturn($this->createTableSchemaStub());
        $table = $schemaMock->getTableSchema('T_constraints_1');

        $this->assertInstanceOf(TableSchema::class, $table);
        $this->assertSame('T_constraints_1', $table->getName());
        $this->assertSame('dbo', $table->getSchemaName());
        $this->assertSame('T_constraints_1', $table->getFullName());
        $this->assertSame(['C_id'], $table->getPrimaryKey());
        $this->assertSame(['C_id', 'C_not_null', 'C_check', 'C_default', 'C_unique'], $table->getColumnNames());
    }

    /**
     * @throws NotSupportedException
     */
    public function testGetTableSchemas(): void
    {
        $db = $this->getConnection();

        $schemaMock = $this->getMockBuilder(Schema::class)
            ->onlyMethods(['findTableNames', 'loadTableSchema'])
            ->setConstructorArgs([$db, DbHelper::getSchemaCache()])
            ->getMock();
        $schemaMock->expects($this->once())->method('findTableNames')->willReturn(['T_constraints_1']);
        $schemaMock->expects($this->once())->method('loadTableSchema')->willReturn($this->createTableSchemaStub());
        $tables = $schemaMock->getTableSchemas('dbo');

        $this->assertCount(count($schemaMock->getTableNames('dbo')), $tables);

        foreach ($tables as $table) {
            $this->assertInstanceOf(TableSchemaInterface::class, $table);
        }
    }

    public function testGetViewNames(): void
    {
        $db = $this->getConnection();

        $schema = $db->getSchema();

        $this->assertSame([], $schema->getViewNames());
    }

    /**
     * @throws ReflectionException
     */
    public function testNormaliceRowKeyCase(): void
    {
        $db = $this->getConnection();

        $schema = $db->getSchema();

        $this->assertSame(
            ['fk_test' => 1],
            Assert::InvokeMethod($schema, 'normalizeRowKeyCase', [['Fk_test' => 1], false]),
        );
        $this->assertSame(
            ['FK_test' => ['uk_test' => 1]],
            Assert::InvokeMethod($schema, 'normalizeRowKeyCase', [['FK_test' => ['UK_test' => 1]], true]),
        );
    }

    public function testRefreshTableSchema(): void
    {
        $db = $this->getConnection(true);

        $schemaMock = $this->getMockBuilder(Schema::class)
            ->onlyMethods(['findTableNames', 'loadTableSchema'])
            ->setConstructorArgs([$db, DbHelper::getSchemaCache()])
            ->getMock();
        $schemaMock
            ->expects($this->exactly(2))
            ->method('loadTableSchema')
            ->will(
                $this->onConsecutiveCalls($this->createTableSchemaStub(), $this->createTableSchemaStub())
            );
        $schemaMock->enableCache(true);
        $noCacheTable = $schemaMock->getTableSchema('T_constraints_1', true);
        $schemaMock->refreshTableSchema('T_constraints_1');
        $refreshedTable = $schemaMock->getTableSchema('T_constraints_1');

        $this->assertNotSame($noCacheTable, $refreshedTable);
    }

    public function testRefreshTableSchemaWithSchemaCaseDisabled(): void
    {
        $db = $this->getConnection(true);

        $schemaMock = $this->getMockBuilder(Schema::class)
            ->onlyMethods(['findTableNames', 'loadTableSchema'])
            ->setConstructorArgs([$db, DbHelper::getSchemaCache()])
            ->getMock();
        $schemaMock
            ->expects($this->exactly(2))
            ->method('loadTableSchema')
            ->will(
                $this->onConsecutiveCalls($this->createTableSchemaStub(), $this->createTableSchemaStub())
            );
        $schemaMock->enableCache(false);
        $noCacheTable = $schemaMock->getTableSchema('T_constraints_1', true);
        $schemaMock->refreshTableSchema('T_constraints_1');
        $refreshedTable = $schemaMock->getTableSchema('T_constraints_1');

        $this->assertNotSame($noCacheTable, $refreshedTable);
    }

    /**
     * @throws ReflectionException
     */
    public function testResolveTableName(): void
    {
        $db = $this->getConnection();

        $schema = $db->getSchema();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage('Yiisoft\Db\Tests\Support\Stub\Schema does not support resolving table names.');

        Assert::invokeMethod($schema, 'resolveTableName', ['customer']);
    }

    /**
     * @throws ReflectionException
     */
    public function testSetTableMetadata(): void
    {
        $db = $this->getConnection();

        $schema = $db->getSchema();

        $checkConstraint = [
            (new CheckConstraint())
                ->columnNames(['col1', 'col2'])
                ->expression('col1 > col2')
                ->name('check_1'),
        ];
        Assert::invokeMethod($schema, 'setTableMetadata', ['T_constraints_1', 'checks', $checkConstraint]);

        $this->assertSame($checkConstraint, $schema->getTableChecks('T_constraints_1'));
    }

    private function createTableSchemaStub(): TableSchemaInterface
    {
        // defined column C_id
        $columnCid = new ColumnSchema('C_id');
        $columnCid->autoIncrement(true);
        $columnCid->dbType('int');
        $columnCid->primaryKey(true);
        $columnCid->phpType('integer');
        $columnCid->type('integer');

        // defined column C_not_null
        $columnCNotNull = new ColumnSchema('C_not_null');
        $columnCNotNull->dbType('int');
        $columnCNotNull->phpType('int');
        $columnCNotNull->type('int');

        // defined column C_check
        $columnCCheck = new ColumnSchema('C_check');
        $columnCCheck->dbType('varchar(255)');
        $columnCCheck->phpType('string');
        $columnCCheck->type('string');

        // defined column C_default
        $columnCDefault = new ColumnSchema('C_default');
        $columnCDefault->dbType('int');
        $columnCDefault->phpType('integer');
        $columnCDefault->type('integer');

        // defined column C_unique
        $columnCUnique = new ColumnSchema('C_unique');
        $columnCUnique->dbType('int');
        $columnCUnique->phpType('integer');
        $columnCUnique->type('integer');

        // defined table T_constraints_1
        $tableSchema = new TableSchema();
        $tableSchema->column('C_id', $columnCid);
        $tableSchema->column('C_not_null', $columnCNotNull);
        $tableSchema->column('C_check', $columnCCheck);
        $tableSchema->column('C_default', $columnCDefault);
        $tableSchema->column('C_unique', $columnCUnique);
        $tableSchema->fullName('T_constraints_1');
        $tableSchema->name('T_constraints_1');
        $tableSchema->primaryKey('C_id');
        $tableSchema->schemaName('dbo');

        return $tableSchema;
    }
}
