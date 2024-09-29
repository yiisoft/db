<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Schema;

use Yiisoft\Db\Constraint\Check;
use Yiisoft\Db\Constraint\DefaultValue;
use Yiisoft\Db\Constraint\ForeignKey;
use Yiisoft\Db\Constraint\Index;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Schema\Column\ColumnBuilder;
use Yiisoft\Db\Schema\TableSchema;
use Yiisoft\Db\Schema\TableSchemaInterface;
use Yiisoft\Db\Tests\AbstractSchemaTest;
use Yiisoft\Db\Tests\Support\Assert;
use Yiisoft\Db\Tests\Support\DbHelper;
use Yiisoft\Db\Tests\Support\Stub\Schema;
use Yiisoft\Db\Tests\Support\TestTrait;

/**
 * @group db
 */
final class SchemaTest extends AbstractSchemaTest
{
    use TestTrait;

    public function testFindTableNames(): void
    {
        $db = $this->getConnection();

        $schema = $db->getSchema();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage('Yiisoft\Db\Tests\Support\Stub\Schema does not support fetching all table names.');

        Assert::invokeMethod($schema, 'findTableNames', ['dbo']);
    }

    public function testFindViewNames(): void
    {
        $db = $this->getConnection();

        $schema = $db->getSchema();

        $this->assertSame([], Assert::invokeMethod($schema, 'findViewNames', ['dbo']));
    }

    public function testGetSchemaChecks(): void
    {
        $db = $this->getConnection();

        $checks = [new Check('check_1', ['col1', 'col2'], 'col1 > col2')];
        $schemaMock = $this->getMockBuilder(Schema::class)
            ->onlyMethods(['findTableNames', 'loadTableChecks'])
            ->setConstructorArgs([$db, DbHelper::getSchemaCache()])
            ->getMock();
        $schemaMock->expects($this->once())->method('findTableNames')->willReturn(['T_constraints_1']);
        $schemaMock->expects($this->once())->method('loadTableChecks')->willReturn($checks);
        $tableChecks = $schemaMock->getSchemaChecks();

        $this->assertSame([$checks], $tableChecks);
    }

    public function testGetSchemaDefaultValues(): void
    {
        $db = $this->getConnection();

        $defaultValues = [new DefaultValue('DF__T_constra__C_def__6203C3C6', ['C_default'], '((0))')];
        $schemaMock = $this->getMockBuilder(Schema::class)
            ->onlyMethods(['findTableNames', 'loadTableDefaultValues'])
            ->setConstructorArgs([$db, DbHelper::getSchemaCache()])
            ->getMock();
        $schemaMock->expects($this->once())->method('findTableNames')->willReturn(['T_constraints_1']);
        $schemaMock->expects($this->once())->method('loadTableDefaultValues')->willReturn($defaultValues);
        $tableDefaultValues = $schemaMock->getSchemaDefaultValues();

        $this->assertSame([$defaultValues], $tableDefaultValues);
    }

    public function testGetSchemaForeignKeys(): void
    {
        $db = $this->getConnection();

        $foreignKeys = [new ForeignKey(
            'CN_constraints_3',
            ['C_fk_id_1, C_fk_id_2'],
            'dev',
            'T_constraints_2',
            ['C_id_1', 'C_id_2'],
        )];
        $schemaMock = $this->getMockBuilder(Schema::class)
            ->onlyMethods(['findTableNames', 'loadTableForeignKeys'])
            ->setConstructorArgs([$db, DbHelper::getSchemaCache()])
            ->getMock();
        $schemaMock->expects($this->once())->method('findTableNames')->willReturn(['T_constraints_1']);
        $schemaMock->expects($this->once())->method('loadTableForeignKeys')->willReturn($foreignKeys);
        $tableForeignKeys = $schemaMock->getSchemaForeignKeys();

        $this->assertSame([$foreignKeys], $tableForeignKeys);
    }

    public function testGetSchemaIndexes(): void
    {
        $db = $this->getConnection();

        $indexes = [new Index('PK__T_constr__A9FAE80AC2B18E65', ['"C_id'], true, true)];
        $schemaMock = $this->getMockBuilder(Schema::class)
            ->onlyMethods(['findTableNames', 'loadTableIndexes'])
            ->setConstructorArgs([$db, DbHelper::getSchemaCache()])
            ->getMock();
        $schemaMock->expects($this->once())->method('findTableNames')->willReturn(['T_constraints_1']);
        $schemaMock->expects($this->once())->method('loadTableIndexes')->willReturn($indexes);
        $tableIndexes = $schemaMock->getSchemaIndexes();

        $this->assertSame([$indexes], $tableIndexes);
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

    public function testGetSchemaNamesWithSchema(): void
    {
        $db = $this->getConnection();

        $schema = $db->getSchema();
        Assert::setPropertyValue($schema, 'schemaNames', ['dbo', 'public']);

        $this->assertSame(['dbo', 'public'], $schema->getSchemaNames());
    }

    public function testHasSchema(): void
    {
        $db = $this->getConnection();

        $schema = $db->getSchema();
        Assert::setPropertyValue($schema, 'schemaNames', ['dbo', 'public']);

        $this->assertTrue($schema->hasSchema('dbo'));
        $this->assertTrue($schema->hasSchema('public'));
        $this->assertFalse($schema->hasSchema('no_such_schema'));

        $db->close();
    }

    public function testGetSchemaPrimaryKeys(): void
    {
        $db = $this->getConnection();

        $pksConstraint = new Index('PK__T_constr__A9FAE80AC2B18E65', ['"C_id'], true, true);
        $schemaMock = $this->getMockBuilder(Schema::class)
            ->onlyMethods(['findTableNames', 'getTablePrimaryKey'])
            ->setConstructorArgs([$db, DbHelper::getSchemaCache()])
            ->getMock();
        $schemaMock->expects($this->once())->method('findTableNames')->willReturn(['T_constraints_1']);
        $schemaMock->expects($this->once())->method('getTablePrimaryKey')->willReturn($pksConstraint);
        $tablePks = $schemaMock->getSchemaPrimaryKeys();

        $this->assertIsArray($tablePks);
        $this->assertContainsOnlyInstancesOf(Index::class, $tablePks);
    }

    public function testGetSchemaUniques(): void
    {
        $db = $this->getConnection();

        $uniquesConstraint = [new Index('CN_unique', ['C_unique'], true)];
        $schemaMock = $this->getMockBuilder(Schema::class)
            ->onlyMethods(['findTableNames', 'getTableUniques'])
            ->setConstructorArgs([$db, DbHelper::getSchemaCache()])
            ->getMock();
        $schemaMock->expects($this->once())->method('findTableNames')->willReturn(['T_constraints_1']);
        $schemaMock->expects($this->once())->method('getTableUniques')->willReturn($uniquesConstraint);
        $tableUniques = $schemaMock->getSchemaUniques();

        $this->assertIsArray($tableUniques);

        foreach ($tableUniques as $uniques) {
            $this->assertIsArray($uniques);
            $this->assertContainsOnlyInstancesOf(Index::class, $uniques);
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

    public function testSetTableMetadata(): void
    {
        $db = $this->getConnection();

        $schema = $db->getSchema();

        $check = [new Check('check_1', ['col1', 'col2'], 'col1 > col2')];
        Assert::invokeMethod($schema, 'setTableMetadata', ['T_constraints_1', 'checks', $check]);

        $this->assertSame($check, $schema->getTableChecks('T_constraints_1'));
    }

    public function testGetResultColumn(): void
    {
        $db = $this->getConnection();
        $schema = $db->getSchema();

        $this->assertNull($schema->getResultColumn([]));

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage('Yiisoft\Db\Tests\Support\Stub\Schema::loadResultColumn is not supported by this DBMS.');

        $schema->getResultColumn(['native_type' => 'integer']);
    }

    private function createTableSchemaStub(): TableSchemaInterface
    {
        // defined table T_constraints_1
        return (new TableSchema('T_constraints_1','dbo'))
            ->columns([
                'C_id' => ColumnBuilder::primaryKey()->dbType('int'),
                'C_not_null' => ColumnBuilder::integer()->dbType('int'),
                'C_check' => ColumnBuilder::string()->dbType('varchar(255)'),
                'C_default' => ColumnBuilder::integer()->dbType('int'),
                'C_unique' => ColumnBuilder::integer()->dbType('int'),
            ]);
    }
}
