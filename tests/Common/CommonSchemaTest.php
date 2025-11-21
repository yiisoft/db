<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Common;

use PDO;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Depends;
use Throwable;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Constant\DataType;
use Yiisoft\Db\Constraint\Check;
use Yiisoft\Db\Constraint\DefaultValue;
use Yiisoft\Db\Constraint\ForeignKey;
use Yiisoft\Db\Constraint\Index;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Schema\Column\ColumnBuilder;
use Yiisoft\Db\Schema\Column\ColumnInterface;
use Yiisoft\Db\Schema\TableSchemaInterface;
use Yiisoft\Db\Tests\Provider\SchemaProvider;
use Yiisoft\Db\Tests\Support\Assert;
use Yiisoft\Db\Tests\Support\IntegrationTestCase;

use function count;
use function mb_chr;

abstract class CommonSchemaTest extends IntegrationTestCase
{
    public function testGetDataType(): void
    {
        $values = [
            [null, DataType::NULL],
            ['', DataType::STRING],
            ['hello', DataType::STRING],
            [0, DataType::INTEGER],
            [1, DataType::INTEGER],
            [1337, DataType::INTEGER],
            [true, DataType::BOOLEAN],
            [false, DataType::BOOLEAN],
            [$fp = fopen(__FILE__, 'rb'), DataType::LOB],
        ];

        $schema = $this->getSharedConnection()->getSchema();

        foreach ($values as $value) {
            $this->assertSame(
                $value[1],
                $schema->getDataType($value[0]),
                'type for value ' . print_r($value[0], true) . ' does not match.',
            );
        }

        fclose($fp);
    }

    public function testRefresh(): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture();
        $schema = $db->getSchema();

        try {
            $this->assertNotEmpty($schema->getTableNames());
            $this->assertNotEmpty($schema->getViewNames());
            $this->assertNotEmpty($schema->getSchemaNames());
        } catch (NotSupportedException) {
        }

        $schema->refresh();

        $this->assertSame([], Assert::getPropertyValue($schema, 'tableMetadata'));
        $this->assertSame([], Assert::getPropertyValue($schema, 'tableNames'));
        $this->assertSame([], Assert::getPropertyValue($schema, 'schemaNames'));
        $this->assertSame([], Assert::getPropertyValue($schema, 'viewNames'));
    }

    public function testColumnComment(): void
    {
        $db = $this->getSharedConnection();

        $command = $db->createCommand();
        $schema = $db->getSchema();

        if ($schema->getTableSchema('testCommentTable') !== null) {
            $command->dropTable('testCommentTable')->execute();
        }

        $command->createTable('testCommentTable', ['bar' => ColumnType::INTEGER,])->execute();
        $command->addCommentOnColumn('testCommentTable', 'bar', 'Test comment for column.')->execute();

        $this->assertSame(
            'Test comment for column.',
            $schema->getTableSchema('testCommentTable')->getColumn('bar')->getComment(),
        );
    }

    #[DataProviderExternal(SchemaProvider::class, 'columns')]
    public function testColumns(array $columns, string $tableName, ?string $dump = null): void
    {
        $this->assertTableColumns($columns, $tableName, $dump);
    }

    public function testCompositeFk(): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture();

        $schema = $db->getSchema();
        $table = $schema->getTableSchema('composite_fk');

        $this->assertNotNull($table);

        $foreignKeys = $table->getForeignKeys();
        $foreignKey = $foreignKeys['FK_composite_fk_order_item'];

        $this->assertCount(1, $foreignKeys);
        $this->assertSame('FK_composite_fk_order_item', $foreignKey->name);
        $this->assertSame('order_item', $foreignKey->foreignTableName);
        $this->assertSame(['order_id', 'item_id'], $foreignKey->foreignColumnNames);
    }

    public function testConstraintTablesExistance(): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture();

        $tableNames = ['T_constraints_1', 'T_constraints_2', 'T_constraints_3', 'T_constraints_4'];
        $schema = $db->getSchema();

        foreach ($tableNames as $tableName) {
            $tableSchema = $schema->getTableSchema($tableName);
            $this->assertInstanceOf(TableSchemaInterface::class, $tableSchema, $tableName);
        }
    }

    public function testGetColumnNoExist(): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture();

        $schema = $db->getSchema();
        $table = $schema->getTableSchema('negative_default_values');

        $this->assertNotNull($table);
        $this->assertNull($table->getColumn('no_exist'));
    }

    public function testGetDefaultSchema(): void
    {
        $schema = $this->getSharedConnection()->getSchema();

        $this->assertSame('', $schema->getDefaultSchema());
    }

    public function testGetNonExistingTableSchema(): void
    {
        $schema = $this->getSharedConnection()->getSchema();

        $this->assertNull($schema->getTableSchema('nonexisting_table'));
    }

    public function testGetSchemaChecks(): void
    {
        $schema = $this->getSharedConnection()->getSchema();
        $tableChecks = $schema->getSchemaChecks();

        $this->assertIsArray($tableChecks);

        foreach ($tableChecks as $checks) {
            $this->assertIsArray($checks);
            $this->assertContainsOnlyInstancesOf(Check::class, $checks);
        }
    }

    public function testGetSchemaDefaultValues(): void
    {
        $schema = $this->getSharedConnection()->getSchema();
        $tableDefaultValues = $schema->getSchemaDefaultValues();

        $this->assertIsArray($tableDefaultValues);

        foreach ($tableDefaultValues as $defaultValues) {
            $this->assertIsArray($defaultValues);
            $this->assertContainsOnlyInstancesOf(DefaultValue::class, $defaultValues);
        }
    }

    public function testGetSchemaForeignKeys(): void
    {
        $schema = $this->getSharedConnection()->getSchema();
        $tableForeignKeys = $schema->getSchemaForeignKeys();

        $this->assertIsArray($tableForeignKeys);

        foreach ($tableForeignKeys as $foreignKeys) {
            $this->assertIsArray($foreignKeys);
            $this->assertContainsOnlyInstancesOf(ForeignKey::class, $foreignKeys);
        }
    }

    public function testGetSchemaIndexes(): void
    {
        $schema = $this->getSharedConnection()->getSchema();
        $tableIndexes = $schema->getSchemaIndexes();

        $this->assertIsArray($tableIndexes);

        foreach ($tableIndexes as $indexes) {
            $this->assertIsArray($indexes);
            $this->assertContainsOnlyInstancesOf(Index::class, $indexes);
        }
    }

    public function testGetSchemaPrimaryKeys(): void
    {
        $schema = $this->getSharedConnection()->getSchema();
        $tablePks = $schema->getSchemaPrimaryKeys();

        $this->assertIsArray($tablePks);
        $this->assertContainsOnlyInstancesOf(Index::class, $tablePks);
    }

    public function testGetSchemaUniques(): void
    {
        $schema = $this->getSharedConnection()->getSchema();
        $tableUniques = $schema->getSchemaUniques();

        $this->assertIsArray($tableUniques);

        foreach ($tableUniques as $uniques) {
            $this->assertIsArray($uniques);
            $this->assertContainsOnlyInstancesOf(Index::class, $uniques);
        }
    }

    public function testGetTableChecks(): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture();

        $schema = $db->getSchema();
        $tableChecks = $schema->getTableChecks('T_constraints_1');

        $this->assertIsArray($tableChecks);

        $this->assertContainsOnlyInstancesOf(Check::class, $tableChecks);
    }

    #[DataProviderExternal(SchemaProvider::class, 'pdoAttributes')]
    public function testGetTableNames(array $pdoAttributes): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture();

        foreach ($pdoAttributes as $name => $value) {
            if ($name === PDO::ATTR_EMULATE_PREPARES) {
                continue;
            }

            $db->getPdo()?->setAttribute($name, $value);
        }

        $schema = $db->getSchema();
        $tablesNames = $schema->getTableNames();

        $this->assertContains('customer', $tablesNames);
        $this->assertContains('category', $tablesNames);
        $this->assertContains('item', $tablesNames);
        $this->assertContains('order', $tablesNames);
        $this->assertContains('order_item', $tablesNames);
        $this->assertContains('type', $tablesNames);
        $this->assertContains('animal', $tablesNames);
        $this->assertContains('animal_view', $tablesNames);
    }

    public function testHasTable(): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture();

        $schema = $db->getSchema();

        $tempTableName = 'testTemporaryTable';
        $db->createCommand()
            ->createTable(
                $tempTableName,
                [
                    'id' => ColumnBuilder::primaryKey(),
                    'name' => ColumnBuilder::string()->notNull(),
                ],
            )
            ->execute();

        $this->assertTrue($schema->hasTable('order'));
        $this->assertTrue($schema->hasTable($tempTableName));
        $this->assertFalse($schema->hasTable('no_such_table'));

        $db->createCommand()->dropTable($tempTableName)->execute();

        $this->assertFalse($schema->hasTable($tempTableName));
        $this->assertTrue($schema->hasTable('order'));
    }

    public function testHasTableWithSqlRemoving(): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture();

        $schema = $db->getSchema();

        $tempTableName = 'testTemporaryTable';
        $db->createCommand()
            ->createTable(
                $tempTableName,
                [
                    'id' => ColumnBuilder::primaryKey(),
                    'name' => ColumnBuilder::string()->notNull(),
                ],
            )
            ->execute();

        $this->assertTrue($schema->hasTable('order'));
        $this->assertTrue($schema->hasTable($tempTableName));
        $this->assertFalse($schema->hasTable('no_such_table'));

        $db->createCommand('DROP TABLE ' . $db->getQuoter()->quoteTableName($tempTableName))->execute();

        $this->assertTrue($schema->hasTable($tempTableName));
        $this->assertFalse($schema->hasTable($tempTableName, '', true));
    }

    #[DataProviderExternal(SchemaProvider::class, 'tableSchema')]
    public function testGetTableSchema(string $name, string $expectedName): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture();

        $tableSchema = $db->getSchema()->getTableSchema($name);

        $this->assertInstanceOf(TableSchemaInterface::class, $tableSchema);
        $this->assertEquals($expectedName, $tableSchema->getName());
    }

    #[DataProviderExternal(SchemaProvider::class, 'pdoAttributes')]
    public function testGetTableSchemas(array $pdoAttributes): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture();

        foreach ($pdoAttributes as $name => $value) {
            if ($name === PDO::ATTR_EMULATE_PREPARES) {
                continue;
            }

            $db->getPdo()?->setAttribute($name, $value);
        }

        $schema = $db->getSchema();
        $tables = $schema->getTableSchemas();

        $this->assertCount(count($schema->getTableNames()), $tables);

        foreach ($tables as $table) {
            $this->assertInstanceOf(TableSchemaInterface::class, $table);
        }
    }

    public function testGetTableSchemaWithAttrCase(): void
    {
        $db = $this->createConnection();
        $this->loadFixture(db: $db);

        $schema = $db->getSchema();
        $db->getActivePdo()->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);

        $this->assertCount(count($schema->getTableNames()), $schema->getTableSchemas());

        $db->getActivePdo()->setAttribute(PDO::ATTR_CASE, PDO::CASE_UPPER);

        $this->assertCount(count($schema->getTableNames()), $schema->getTableSchemas());

        $db->close();
    }

    public function testGetViewNames(): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture();

        $schema = $db->getSchema();
        $views = $schema->getViewNames();

        $this->assertSame(['animal_view'], $views);
    }

    public function testHasView(): void
    {
        $db = $this->getSharedConnection();
        $quoter = $db->getQuoter();

        $db->createCommand('DROP VIEW IF EXISTS '.$quoter->quoteTableName('v1'))->execute();
        $db->createCommand('DROP VIEW IF EXISTS '.$quoter->quoteTableName('v2'))->execute();
        $db->createCommand()->createView('v1', 'SELECT 1 AS col1')->execute();
        $db->createCommand()->createView('v2', 'SELECT 1 AS col1')->execute();

        $schema = $db->getSchema();

        $this->assertTrue($schema->hasView('v1'));
        $this->assertTrue($schema->hasView('v2'));
        $this->assertFalse($schema->hasView('v3'));

        $db->createCommand()->dropView('v1')->execute();

        $this->assertFalse($schema->hasView('v1'));
        $this->assertTrue($schema->hasView('v2'));
        $this->assertFalse($schema->hasView('v3'));
        $this->assertFalse($schema->hasView('v1', refresh: true));
        $this->assertTrue($schema->hasView('v2', refresh: true));
        $this->assertFalse($schema->hasView('v3', refresh: true));
    }

    public function testHasViewWithSqlRemoving(): void
    {
        $db = $this->getSharedConnection();
        $quoter = $db->getQuoter();

        $db->createCommand('DROP VIEW IF EXISTS '.$quoter->quoteTableName('v1'))->execute();
        $db->createCommand('DROP VIEW IF EXISTS '.$quoter->quoteTableName('v2'))->execute();
        $db->createCommand()->createView('v1', 'SELECT 1 AS col1')->execute();
        $db->createCommand()->createView('v2', 'SELECT 1 AS col1')->execute();

        $schema = $db->getSchema();

        $this->assertTrue($schema->hasView('v1'));
        $this->assertTrue($schema->hasView('v2'));
        $this->assertFalse($schema->hasView('v3'));

        $db->createCommand('DROP VIEW ' . $db->getQuoter()->quoteTableName('v1'))->execute();

        $this->assertTrue($schema->hasView('v1'));
        $this->assertTrue($schema->hasView('v2'));
        $this->assertFalse($schema->hasView('v3'));
        $this->assertFalse($schema->hasView('v1', refresh: true));
        $this->assertTrue($schema->hasView('v2', refresh: true));
        $this->assertFalse($schema->hasView('v3', refresh: true));
    }

    public function testNegativeDefaultValues(): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture();

        $schema = $db->getSchema();
        $table = $schema->getTableSchema('negative_default_values');

        $this->assertNotNull($table);
        $this->assertSame(-123, $table->getColumn('tinyint_col')?->getDefaultValue());
        $this->assertSame(-123, $table->getColumn('smallint_col')?->getDefaultValue());
        $this->assertSame(-123, $table->getColumn('int_col')?->getDefaultValue());
        $this->assertSame(-123, $table->getColumn('bigint_col')?->getDefaultValue());
        $this->assertSame(-12345.6789, $table->getColumn('float_col')?->getDefaultValue());
        $this->assertEquals(-33.22, $table->getColumn('numeric_col')?->getDefaultValue());
    }

    public function testQuoterEscapingValue(): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture();

        $quoter = $db->getQuoter();
        $db->createCommand('DELETE FROM [[quoter]]')->execute();
        $data = $this->generateQuoterEscapingValues();

        foreach ($data as $index => $value) {
            $quotedName = $quoter->quoteValue('testValue_' . $index);
            $quoteValue = $quoter->quoteValue($value);
            $db->createCommand(
                <<<SQL
                INSERT INTO [[quoter]] ([[name]], [[description]]) VALUES ($quotedName, $quoteValue)
                SQL,
            )->execute();
            $result = $db->createCommand(
                <<<SQL
                SELECT * FROM [[quoter]] WHERE [[name]]=$quotedName
                SQL,
            )->queryOne();

            $this->assertSame($value, $result['description']);
        }
    }

    #[Depends('testSchemaCache')]
    public function testRefreshTableSchema(): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture();

        $schema = $db->getSchema();
        $schema->enableCache(true);
        $noCacheTable = $schema->getTableSchema('type', true);
        $schema->refreshTableSchema('type');
        $refreshedTable = $schema->getTableSchema('type');

        $this->assertNotSame($noCacheTable, $refreshedTable);
    }

    public function testSchemaCache(): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture();

        $schema = $db->getSchema();
        $schema->enableCache(true);
        $noCacheTable = $schema->getTableSchema('type', true);
        $cachedTable = $schema->getTableSchema('type');

        $this->assertSame($noCacheTable, $cachedTable);

        $db->createCommand()->renameTable('type', 'type_test')->execute();
        $noCacheTable = $schema->getTableSchema('type', true);

        $this->assertNotSame($noCacheTable, $cachedTable);

        $db->createCommand()->renameTable('type_test', 'type')->execute();
    }

    public function testSchemaCacheExtreme(): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture();

        $command = $db->createCommand();
        $schema = $db->getSchema();
        $schema->enableCache(true);

        if ($schema->getTableSchema('{{test_schema_cache}}') !== null) {
            $command->dropTable('{{test_schema_cache}}')->execute();
        }

        $command->createTable('{{test_schema_cache}}', ['int1' => 'integer null'])->execute();

        $schemaNotCache = $schema->getTableSchema('{{test_schema_cache}}', true);

        $this->assertNotNull($schemaNotCache);

        $schemaCached = $schema->getTableSchema('{{test_schema_cache}}');

        $this->assertNotNull($schemaCached);
        $this->assertSame($schemaCached, $schemaNotCache);

        for ($i = 2; $i <= 20; $i++) {
            $command->addColumn('{{test_schema_cache}}', 'int' . $i, 'integer null')->execute();

            $schemaCached = $schema->getTableSchema('{{test_schema_cache}}');

            $this->assertNotNull($schemaCached);
            $this->assertNotSame($schemaCached, $schemaNotCache);
        }

        $this->assertCount(20, $schemaCached->getColumns());
    }

    #[DataProviderExternal(SchemaProvider::class, 'tableSchemaCachePrefixes')]
    public function testTableSchemaCacheWithTablePrefixes(
        string $tablePrefix,
        string $tableName,
        string $testTablePrefix,
        string $testTableName,
    ): void {
        $db = $this->createConnection();
        $this->loadFixture(db: $db);

        $schema = $db->getSchema();
        $schema->enableCache(true);
        $db->setTablePrefix($tablePrefix);
        $noCacheTable = $schema->getTableSchema($tableName, true);

        $this->assertInstanceOf(TableSchemaInterface::class, $noCacheTable);

        /* Compare */
        $db->setTablePrefix($testTablePrefix);
        $testNoCacheTable = $schema->getTableSchema($testTableName);

        $this->assertSame($noCacheTable, $testNoCacheTable);

        $db->setTablePrefix($tablePrefix);
        $schema->refreshTableSchema($tableName);
        $refreshedTable = $schema->getTableSchema($tableName);

        $this->assertInstanceOf(TableSchemaInterface::class, $refreshedTable);
        $this->assertNotSame($noCacheTable, $refreshedTable);

        /* Compare */
        $db->setTablePrefix($testTablePrefix);
        $schema->refreshTableSchema($testTablePrefix);
        $testRefreshedTable = $schema->getTableSchema($testTableName);

        $this->assertInstanceOf(TableSchemaInterface::class, $testRefreshedTable);
        $this->assertEquals($refreshedTable, $testRefreshedTable);
        $this->assertNotSame($testNoCacheTable, $testRefreshedTable);

        $db->close();
    }

    #[DataProviderExternal(SchemaProvider::class, 'constraints')]
    public function testTableSchemaConstraints(string $tableName, string $type, mixed $expected): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture();

        $schema = $db->getSchema();

        $exception = null;
        try {
            $constraints = $schema->{"getTable$type"}($tableName);
        } catch (Throwable $exception) {
        }

        $expected === false
            ? $this->assertInstanceOf(NotSupportedException::class, $exception)
            : Assert::constraintsEquals($expected, $constraints);
    }

    #[DataProviderExternal(SchemaProvider::class, 'constraints')]
    public function testTableSchemaConstraintsWithPdoLowercase(string $tableName, string $type, mixed $expected): void
    {
        $db = $this->createConnection();
        $this->loadFixture(db: $db);

        $schema = $db->getSchema();
        $db->getActivePdo()->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);

        $exception = null;
        try {
            $constraints = $schema->{"getTable$type"}($tableName, true);
        } catch (Throwable $exception) {
        }

        $expected === false
            ? $this->assertInstanceOf(NotSupportedException::class, $exception)
            : Assert::constraintsEquals($expected, $constraints);

        $db->close();
    }

    #[DataProviderExternal(SchemaProvider::class, 'constraints')]
    public function testTableSchemaConstraintsWithPdoUppercase(string $tableName, string $type, mixed $expected): void
    {
        $db = $this->createConnection();
        $this->loadFixture(db: $db);

        $schema = $db->getSchema();
        $db->getActivePdo()->setAttribute(PDO::ATTR_CASE, PDO::CASE_UPPER);

        $exception = null;
        try {
            $constraints = $schema->{'getTable' . ucfirst($type)}($tableName, true);
        } catch (Throwable $exception) {
        }

        $expected === false
            ? $this->assertInstanceOf(NotSupportedException::class, $exception)
            : Assert::constraintsEquals($expected, $constraints);

        $db->close();
    }

    public function testWorkWithUniqueConstraint(): void
    {
        $tableName = 'test_table_with';
        $indexName = 't_constraint';
        $columnName = 't_field';

        $db = $this->getSharedConnection();

        $this->createTableForIndexAndConstraintTests($db, $tableName, $columnName);
        $db->createCommand()->addUnique($tableName, $indexName, $columnName)->execute();

        $this->assertEquals(
            [$indexName => new Index($indexName, [$columnName], true)],
            $db->getSchema()->getTableUniques($tableName),
        );

        $db->createCommand()->dropUnique($tableName, $indexName)->execute();

        $constraints = $db->getSchema()->getTableUniques($tableName);

        $this->assertSame([], $constraints);

        $this->dropTableForIndexAndConstraintTests($db, $tableName);
    }

    public function testWorkWithCheckConstraint(): void
    {
        $tableName = 'test_table_with';
        $constraintName = 't_constraint';
        $columnName = 't_field';

        $db = $this->getSharedConnection();

        $this->createTableForIndexAndConstraintTests($db, $tableName, $columnName, 'int');
        $db->createCommand()->addCheck(
            $tableName,
            $constraintName,
            $db->getQuoter()->quoteColumnName($columnName) . ' > 0',
        )->execute();

        /** @var Check[] $constraints */
        $constraints = $db->getSchema()->getTableChecks($tableName, true);

        $this->assertIsArray($constraints);
        $this->assertCount(1, $constraints);
        $this->assertInstanceOf(Check::class, $constraints[$constraintName]);
        $this->assertEquals($constraintName, $constraints[$constraintName]->name);
        $this->assertEquals([$columnName], $constraints[$constraintName]->columnNames);
        $this->assertStringContainsString($columnName, $constraints[$constraintName]->expression);

        $db->createCommand()->dropCheck($tableName, $constraintName)->execute();

        $constraints = $db->getSchema()->getTableChecks($tableName, true);

        $this->assertSame([], $constraints);

        $this->dropTableForIndexAndConstraintTests($db, $tableName);
    }

    public function testWorkWithDefaultValueConstraint(): void
    {
        $tableName = 'test_table_with';
        $constraintName = 't_constraint';
        $columnName = 't_field';

        $db = $this->getSharedConnection();

        $this->createTableForIndexAndConstraintTests($db, $tableName, $columnName);
        $db->createCommand()->addDefaultValue($tableName, $constraintName, $columnName, 919)->execute();

        /** @var DefaultValue[] $constraints */
        $constraints = $db->getSchema()->getTableDefaultValues($tableName, true);

        $this->assertIsArray($constraints);
        $this->assertCount(1, $constraints);
        $this->assertEquals(
            new DefaultValue($constraintName, [$columnName], '((919))'),
            $constraints[$constraintName],
        );

        $db->createCommand()->dropDefaultValue($tableName, $constraintName)->execute();

        $constraints = $db->getSchema()->getTableDefaultValues($tableName, true);

        $this->assertSame([], $constraints);

        $this->dropTableForIndexAndConstraintTests($db, $tableName);
    }

    public function testWorkWithPrimaryKeyConstraint(): void
    {
        $tableName = 'test_table_with';
        $constraintName = 't_constraint';
        $columnName = 't_field';

        $db = $this->getSharedConnection();

        $this->createTableForIndexAndConstraintTests($db, $tableName, $columnName);
        $db->createCommand()->addPrimaryKey($tableName, $constraintName, $columnName)->execute();

        $this->assertEquals(
            new Index($constraintName, [$columnName], true, true),
            $db->getSchema()->getTablePrimaryKey($tableName),
        );

        $db->createCommand()->dropPrimaryKey($tableName, $constraintName)->execute();

        $constraints = $db->getSchema()->getTablePrimaryKey($tableName, true);

        $this->assertNull($constraints);

        $this->dropTableForIndexAndConstraintTests($db, $tableName);
    }

    #[DataProviderExternal(SchemaProvider::class, 'withIndexDataProvider')]
    public function testWorkWithIndex(
        ?string $indexType = null,
        ?string $indexMethod = null,
        ?string $columnType = null,
        bool $isPrimary = false,
        bool $isUnique = false,
    ): void {
        $tableName = 'test_table_with';
        $indexName = 't_index';
        $columnName = 't_field';

        $db = $this->getSharedConnection();
        $command = $db->createCommand();

        $this->createTableForIndexAndConstraintTests($db, $tableName, $columnName, $columnType);

        $command->createIndex($tableName, $indexName, $columnName, $indexType, $indexMethod)->execute();

        $this->assertEquals(
            [$indexName => new Index($indexName, [$columnName], $isUnique, $isPrimary)],
            $db->getSchema()->getTableIndexes($tableName),
        );

        $this->dropTableForIndexAndConstraintTests($db, $tableName);
    }

    #[DataProviderExternal(SchemaProvider::class, 'resultColumns')]
    public function testGetResultColumn(?ColumnInterface $expected, array $metadata): void
    {
        $schema = $this->getSharedConnection()->getSchema();

        $this->assertEquals($expected, $schema->getResultColumn($metadata));
    }

    public function testPrimaryKeyOrder(): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture();

        $schema = $db->getSchema();

        $tableSchema = $schema->getTableSchema('order_item');

        $this->assertSame(['order_id', 'item_id'], $tableSchema->getPrimaryKey());
    }

    /**
     * @param ColumnInterface[] $columns
     */
    protected function assertTableColumns(array $columns, string $tableName, ?string $dump = null): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture($dump);

        $table = $db->getTableSchema($tableName, true);

        $this->assertNotNull($table);

        foreach ($columns as $name => &$column) {
            $column = $column->withName($name);

            if ($column->isNotNull() === null) {
                $column->notNull(false);
            }

            if ($column->getDefaultValue() === null) {
                $column->defaultValue(null);
            }
        }

        Assert::arraysEquals($columns, $table->getColumns(), "Columns of table '$tableName'.");
    }

    protected function createTableForIndexAndConstraintTests(
        ConnectionInterface $db,
        string $tableName,
        string $columnName,
        ?string $columnType = null,
    ): void {
        $qb = $db->getQueryBuilder();

        if ($db->getTableSchema($tableName, true) !== null) {
            $db->createCommand($qb->dropTable($tableName))->execute();
        }

        $createTableSql = $qb->createTable(
            $tableName,
            [
                $columnName => $columnType ?? 'int NOT NULL',
            ],
        );

        $db->createCommand($createTableSql)->execute();
        $tableSchema = $db->getTableSchema($tableName, true);
        $this->assertInstanceOf(TableSchemaInterface::class, $tableSchema);
    }

    protected function dropTableForIndexAndConstraintTests(ConnectionInterface $db, $tableName): void
    {
        $qb = $db->getQueryBuilder();

        $db->createCommand($qb->dropTable($tableName))->execute();
        $this->assertNull($db->getTableSchema($tableName, true));
    }

    private function generateQuoterEscapingValues(): array
    {
        $result = [];
        $stringLength = 16;

        for ($i = 32; $i < 128 - $stringLength; $i += $stringLength) {
            $str = '';

            for ($symbol = $i; $symbol < $i + $stringLength; $symbol++) {
                $str .= mb_chr($symbol, 'UTF-8');
            }

            $result[] = $str;
            $str = '';

            for ($symbol = $i; $symbol < $i + $stringLength; $symbol++) {
                $str .= mb_chr($symbol, 'UTF-8') . mb_chr($symbol, 'UTF-8');
            }

            $result[] = $str;
        }

        return $result;
    }
}
