<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Common;

use PDO;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Depends;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Constant\IndexType;
use Yiisoft\Db\Constraint\Check;
use Yiisoft\Db\Constraint\AbstractConstraint;
use Yiisoft\Db\Constraint\DefaultValue;
use Yiisoft\Db\Constraint\ForeignKey;
use Yiisoft\Db\Constraint\Index;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Schema\Column\ColumnBuilder;
use Yiisoft\Db\Schema\Column\ColumnInterface;
use Yiisoft\Db\Schema\TableSchemaInterface;
use Yiisoft\Db\Tests\AbstractSchemaTest;
use Yiisoft\Db\Tests\Provider\SchemaProvider;
use Yiisoft\Db\Tests\Support\Assert;

use function count;
use function gettype;
use function is_array;
use function json_encode;
use function ksort;
use function mb_chr;
use function str_replace;
use function strtolower;

abstract class CommonSchemaTest extends AbstractSchemaTest
{
    public function testColumnComment(): void
    {
        $db = $this->getConnection();

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

        $db->close();
    }

    #[DataProviderExternal(SchemaProvider::class, 'columns')]
    public function testColumns(array $columns, string $tableName): void
    {
        $this->assertTableColumns($columns, $tableName);
    }

    public function testCompositeFk(): void
    {
        $db = $this->getConnection(true);

        $schema = $db->getSchema();
        $table = $schema->getTableSchema('composite_fk');

        $this->assertNotNull($table);

        $fk = $table->getForeignKeys();

        $expectedKey = match ($db->getDriverName()) {
            'mysql', 'sqlsrv' => $fk['FK_composite_fk_order_item'],
            default => $fk['fk_composite_fk_order_item'],
        };

        $this->assertCount(1, $fk);
        $this->assertTrue(isset($expectedKey));
        $this->assertSame('order_item', $expectedKey[0]);
        $this->assertSame('order_id', $expectedKey['order_id']);
        $this->assertSame('item_id', $expectedKey['item_id']);

        $db->close();
    }

    public function testContraintTablesExistance(): void
    {
        $db = $this->getConnection(true);

        $tableNames = ['T_constraints_1', 'T_constraints_2', 'T_constraints_3', 'T_constraints_4'];
        $schema = $db->getSchema();

        foreach ($tableNames as $tableName) {
            $tableSchema = $schema->getTableSchema($tableName);
            $this->assertInstanceOf(TableSchemaInterface::class, $tableSchema, $tableName);
        }

        $db->close();
    }

    public function testFindUniquesIndexes(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $schema = $db->getSchema();

        try {
            $command->dropTable('uniqueIndex')->execute();
        } catch (Exception) {
        }

        $command->createTable(
            'uniqueIndex',
            ['somecol' => 'string', 'someCol2' => 'string', 'someCol3' => 'string'],
        )->execute();
        $tableSchema = $schema->getTableSchema('uniqueIndex', true);

        $this->assertNotNull($tableSchema);

        $uniqueIndexes = $schema->findUniqueIndexes($tableSchema);

        $this->assertSame([], $uniqueIndexes);

        $command->createIndex(
            'uniqueIndex',
            'somecolUnique',
            'somecol',
            IndexType::UNIQUE,
        )->execute();
        $tableSchema = $schema->getTableSchema('uniqueIndex', true);

        $this->assertNotNull($tableSchema);

        $uniqueIndexes = $schema->findUniqueIndexes($tableSchema);

        $this->assertSame(['somecolUnique' => ['somecol']], $uniqueIndexes);

        /**
         * Create another column with upper case letter that fails postgres.
         *
         * @link https://github.com/yiisoft/yii2/issues/10613
         */
        $command->createIndex(
            'uniqueIndex',
            'someCol2Unique',
            'someCol2',
            IndexType::UNIQUE,
        )->execute();
        $tableSchema = $schema->getTableSchema('uniqueIndex', true);

        $this->assertNotNull($tableSchema);

        $uniqueIndexes = $schema->findUniqueIndexes($tableSchema);

        $this->assertSame(['someCol2Unique' => ['someCol2'], 'somecolUnique' => ['somecol']], $uniqueIndexes);

        /** @link https://github.com/yiisoft/yii2/issues/13814 */
        $command->createIndex(
            'uniqueIndex',
            'another unique index',
            'someCol3',
            IndexType::UNIQUE,
        )->execute();
        $tableSchema = $schema->getTableSchema('uniqueIndex', true);

        $this->assertNotNull($tableSchema);

        $uniqueIndexes = $schema->findUniqueIndexes($tableSchema);

        $this->assertSame(
            ['another unique index' => ['someCol3'], 'someCol2Unique' => ['someCol2'], 'somecolUnique' => ['somecol']],
            $uniqueIndexes,
        );

        $db->close();
    }

    public function testGetColumnNoExist(): void
    {
        $db = $this->getConnection(true);

        $schema = $db->getSchema();
        $table = $schema->getTableSchema('negative_default_values');

        $this->assertNotNull($table);
        $this->assertNull($table->getColumn('no_exist'));

        $db->close();
    }

    public function testGetDefaultSchema(): void
    {
        $db = $this->getConnection();

        $schema = $db->getSchema();

        $this->assertNull($schema->getDefaultSchema());

        $db->close();
    }

    public function testGetNonExistingTableSchema(): void
    {
        $db = $this->getConnection();

        $schema = $db->getSchema();

        $this->assertNull($schema->getTableSchema('nonexisting_table'));

        $db->close();
    }

    public function testGetSchemaChecks(): void
    {
        $db = $this->getConnection();

        $schema = $db->getSchema();
        $tableChecks = $schema->getSchemaChecks();

        $this->assertIsArray($tableChecks);

        foreach ($tableChecks as $checks) {
            $this->assertIsArray($checks);
            $this->assertContainsOnlyInstancesOf(Check::class, $checks);
        }

        $db->close();
    }

    public function testGetSchemaDefaultValues(): void
    {
        $db = $this->getConnection();

        $schema = $db->getSchema();
        $tableDefaultValues = $schema->getSchemaDefaultValues();

        $this->assertIsArray($tableDefaultValues);

        foreach ($tableDefaultValues as $defaultValues) {
            $this->assertIsArray($defaultValues);
            $this->assertContainsOnlyInstancesOf(DefaultValue::class, $defaultValues);
        }

        $db->close();
    }

    public function testGetSchemaForeignKeys(): void
    {
        $db = $this->getConnection();

        $schema = $db->getSchema();
        $tableForeignKeys = $schema->getSchemaForeignKeys();

        $this->assertIsArray($tableForeignKeys);

        foreach ($tableForeignKeys as $foreignKeys) {
            $this->assertIsArray($foreignKeys);
            $this->assertContainsOnlyInstancesOf(ForeignKey::class, $foreignKeys);
        }

        $db->close();
    }

    public function testGetSchemaIndexes(): void
    {
        $db = $this->getConnection();

        $schema = $db->getSchema();
        $tableIndexes = $schema->getSchemaIndexes();

        $this->assertIsArray($tableIndexes);

        foreach ($tableIndexes as $indexes) {
            $this->assertIsArray($indexes);
            $this->assertContainsOnlyInstancesOf(Index::class, $indexes);
        }

        $db->close();
    }

    public function testGetSchemaPrimaryKeys(): void
    {
        $db = $this->getConnection();

        $schema = $db->getSchema();
        $tablePks = $schema->getSchemaPrimaryKeys();

        $this->assertIsArray($tablePks);
        $this->assertContainsOnlyInstancesOf(AbstractConstraint::class, $tablePks);

        $db->close();
    }

    public function testGetSchemaUniques(): void
    {
        $db = $this->getConnection();

        $schema = $db->getSchema();
        $tableUniques = $schema->getSchemaUniques();

        $this->assertIsArray($tableUniques);

        foreach ($tableUniques as $uniques) {
            $this->assertIsArray($uniques);
            $this->assertContainsOnlyInstancesOf(AbstractConstraint::class, $uniques);
        }

        $db->close();
    }

    public function testGetTableChecks(): void
    {
        $db = $this->getConnection(true);

        $schema = $db->getSchema();
        $tableChecks = $schema->getTableChecks('T_constraints_1');

        $this->assertIsArray($tableChecks);

        $this->assertContainsOnlyInstancesOf(Check::class, $tableChecks);

        $db->close();
    }

    #[DataProviderExternal(SchemaProvider::class, 'pdoAttributes')]
    public function testGetTableNames(array $pdoAttributes): void
    {
        $db = $this->getConnection(true);

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

        $db->close();
    }

    public function testHasTable(): void
    {
        $db = $this->getConnection(true);

        $schema = $db->getSchema();

        $tempTableName = 'testTemporaryTable';
        $db->createCommand()->createTable($tempTableName, [
            'id' => ColumnBuilder::primaryKey(),
            'name' => ColumnBuilder::string()->notNull(),
        ])->execute();

        $this->assertTrue($schema->hasTable('order'));
        $this->assertTrue($schema->hasTable('category'));
        $this->assertTrue($schema->hasTable($tempTableName));
        $this->assertFalse($schema->hasTable('no_such_table'));

        $db->createCommand('DROP TABLE ' . $db->getQuoter()->quoteTableName($tempTableName))->execute();

        $this->assertTrue($schema->hasTable($tempTableName));
        $this->assertFalse($schema->hasTable($tempTableName, '', true));

        $db->close();
    }

    #[DataProviderExternal(SchemaProvider::class, 'tableSchema')]
    public function testGetTableSchema(string $name, string $expectedName): void
    {
        $db = $this->getConnection(true);

        $tableSchema = $db->getSchema()->getTableSchema($name);

        $this->assertInstanceOf(TableSchemaInterface::class, $tableSchema);
        $this->assertEquals($expectedName, $tableSchema->getName());

        $db->close();
    }

    #[DataProviderExternal(SchemaProvider::class, 'pdoAttributes')]
    public function testGetTableSchemas(array $pdoAttributes): void
    {
        $db = $this->getConnection(true);

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

        $db->close();
    }

    public function testGetTableSchemaWithAttrCase(): void
    {
        $db = $this->getConnection(true);

        $schema = $db->getSchema();
        $db->getActivePdo()->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);

        $this->assertCount(count($schema->getTableNames()), $schema->getTableSchemas());

        $db->getActivePdo()->setAttribute(PDO::ATTR_CASE, PDO::CASE_UPPER);

        $this->assertCount(count($schema->getTableNames()), $schema->getTableSchemas());

        $db->close();
    }

    public function testGetViewNames(): void
    {
        $db = $this->getConnection(true);

        $schema = $db->getSchema();
        $views = $schema->getViewNames();

        $this->assertSame(['animal_view'], $views);

        $db->close();
    }

    public function testHasView(): void
    {
        $db = $this->getConnection(true);

        $schema = $db->getSchema();

        $this->assertTrue($schema->hasView('animal_view'));
        $this->assertFalse($schema->hasView('no_such_view'));

        $db->createCommand()->dropView('animal_view')->execute();

        $this->assertTrue($schema->hasView('animal_view'));
        $this->assertFalse($schema->hasView('animal_view', '', true));

        $db->close();
    }

    public function testNegativeDefaultValues(): void
    {
        $db = $this->getConnection(true);

        $schema = $db->getSchema();
        $table = $schema->getTableSchema('negative_default_values');

        $this->assertNotNull($table);
        $this->assertSame(-123, $table->getColumn('tinyint_col')?->getDefaultValue());
        $this->assertSame(-123, $table->getColumn('smallint_col')?->getDefaultValue());
        $this->assertSame(-123, $table->getColumn('int_col')?->getDefaultValue());
        $this->assertSame(-123, $table->getColumn('bigint_col')?->getDefaultValue());
        $this->assertSame(-12345.6789, $table->getColumn('float_col')?->getDefaultValue());
        $this->assertEquals(-33.22, $table->getColumn('numeric_col')?->getDefaultValue());

        $db->close();
    }

    public function testQuoterEscapingValue(): void
    {
        $db = $this->getConnection(true);

        $quoter = $db->getQuoter();
        $db->createCommand(
            <<<SQL
            DELETE FROM [[quoter]]
            SQL
        )->execute();
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

        $db->close();
    }

    #[Depends('testSchemaCache')]
    public function testRefreshTableSchema(): void
    {
        $db = $this->getConnection(true);

        $schema = $db->getSchema();
        $schema->enableCache(true);
        $noCacheTable = $schema->getTableSchema('type', true);
        $schema->refreshTableSchema('type');
        $refreshedTable = $schema->getTableSchema('type');

        $this->assertNotSame($noCacheTable, $refreshedTable);

        $db->close();
    }

    public function testSchemaCache(): void
    {
        $db = $this->getConnection(true);

        $schema = $db->getSchema();
        $schema->enableCache(true);
        $noCacheTable = $schema->getTableSchema('type', true);
        $cachedTable = $schema->getTableSchema('type');

        $this->assertSame($noCacheTable, $cachedTable);

        $db->createCommand()->renameTable('type', 'type_test')->execute();
        $noCacheTable = $schema->getTableSchema('type', true);

        $this->assertNotSame($noCacheTable, $cachedTable);

        $db->createCommand()->renameTable('type_test', 'type')->execute();

        $db->close();
    }

    public function testSchemaCacheExtreme(): void
    {
        $db = $this->getConnection(true);

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

        $db->close();
    }

    #[DataProviderExternal(SchemaProvider::class, 'tableSchemaCachePrefixes')]
    public function testTableSchemaCacheWithTablePrefixes(
        string $tablePrefix,
        string $tableName,
        string $testTablePrefix,
        string $testTableName
    ): void {
        $db = $this->getConnection(true);

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
        if ($expected === false) {
            $this->expectException(NotSupportedException::class);
        }

        $db = $this->getConnection(true);
        $schema = $db->getSchema();
        $constraints = $schema->{"getTable$type"}($tableName);

        $this->assertMetadataEquals($expected, $constraints);

        $db->close();
    }

    #[DataProviderExternal(SchemaProvider::class, 'constraints')]
    public function testTableSchemaConstraintsWithPdoLowercase(string $tableName, string $type, mixed $expected): void
    {
        if ($expected === false) {
            $this->expectException(NotSupportedException::class);
        }

        $db = $this->getConnection(true);

        $schema = $db->getSchema();
        $db->getActivePdo()->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);
        $constraints = $schema->{"getTable$type"}($tableName, true);

        $this->assertMetadataEquals($expected, $constraints);

        $db->close();
    }

    #[DataProviderExternal(SchemaProvider::class, 'constraints')]
    public function testTableSchemaConstraintsWithPdoUppercase(string $tableName, string $type, mixed $expected): void
    {
        if ($expected === false) {
            $this->expectException(NotSupportedException::class);
        }

        $db = $this->getConnection(true);

        $schema = $db->getSchema();
        $db->getActivePdo()->setAttribute(PDO::ATTR_CASE, PDO::CASE_UPPER);
        $constraints = $schema->{'getTable' . ucfirst($type)}($tableName, true);

        $this->assertMetadataEquals($expected, $constraints);

        $db->close();
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

    protected function assertMetadataEquals($expected, $actual): void
    {
        match (gettype($expected)) {
            'object' => $this->assertIsObject($actual),
            'array' => $this->assertIsArray($actual),
            'NULL' => $this->assertNull($actual),
        };

        if (is_array($expected)) {
            $this->sortConstrainArray($expected);
            $this->sortConstrainArray($actual);
        }

        $this->normalizeConstraints($expected, $actual);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @param ColumnInterface[] $columns
     */
    protected function assertTableColumns(array $columns, string $tableName): void
    {
        $db = $this->getConnection(true);

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

        $db->close();
    }

    private function sortConstrainArray(array &$array): void
    {
        $newArray = [];

        foreach ($array as $value) {
            $key = (array) $value;
            unset($key['name']);

            $newArray[strtolower(json_encode($key, JSON_THROW_ON_ERROR))] = $value;
        }

        ksort($newArray, SORT_STRING);
        $array = array_values($newArray);
    }

    private function normalizeConstraints($expected, &$actual): void
    {
        if (is_array($expected)) {
            foreach ($expected as $key => $value) {
                $this->normalizeConstraintPair($value, $actual[$key]);
            }
        } elseif ($expected instanceof AbstractConstraint && $actual instanceof AbstractConstraint) {
            $this->normalizeConstraintPair($expected, $actual);
        }
    }

    private function normalizeConstraintPair(AbstractConstraint $expectedConstraint, AbstractConstraint &$actualConstraint): void
    {
        if ($expectedConstraint::class !== $actualConstraint::class) {
            return;
        }

        if ($expectedConstraint->name === '') {
            Assert::setPropertyValue($actualConstraint, 'name', '');
        }
    }

    public function testWorkWithUniqueConstraint(): void
    {
        $tableName = 'test_table_with';
        $indexName = 't_constraint';
        $columnName = 't_field';

        $db = $this->getConnection();

        $this->createTableForIndexAndConstraintTests($db, $tableName, $columnName);
        $db->createCommand()->addUnique($tableName, $indexName, $columnName)->execute();

        $this->assertEquals(
            [new Index($indexName, [$columnName], true)],
            $db->getSchema()->getTableUniques($tableName),
        );

        $db->createCommand()->dropUnique($tableName, $indexName)->execute();

        $constraints = $db->getSchema()->getTableUniques($tableName);

        $this->assertSame([], $constraints);

        $this->dropTableForIndexAndConstraintTests($db, $tableName);

        $db->close();
    }

    public function testWorkWithCheckConstraint(): void
    {
        $tableName = 'test_table_with';
        $constraintName = 't_constraint';
        $columnName = 't_field';

        $db = $this->getConnection();

        $this->createTableForIndexAndConstraintTests($db, $tableName, $columnName, 'int');
        $db->createCommand()->addCheck(
            $tableName,
            $constraintName,
            $db->getQuoter()->quoteColumnName($columnName) . ' > 0'
        )->execute();

        /** @var Check[] $constraints */
        $constraints = $db->getSchema()->getTableChecks($tableName, true);

        $this->assertIsArray($constraints);
        $this->assertCount(1, $constraints);
        $this->assertInstanceOf(Check::class, $constraints[0]);
        $this->assertEquals($constraintName, $constraints[0]->name);
        $this->assertEquals([$columnName], $constraints[0]->columnNames);
        $this->assertStringContainsString($columnName, $constraints[0]->expression);

        $db->createCommand()->dropCheck($tableName, $constraintName)->execute();

        $constraints = $db->getSchema()->getTableChecks($tableName, true);

        $this->assertSame([], $constraints);

        $this->dropTableForIndexAndConstraintTests($db, $tableName);

        $db->close();
    }

    public function testWorkWithDefaultValueConstraint(): void
    {
        $tableName = 'test_table_with';
        $constraintName = 't_constraint';
        $columnName = 't_field';

        $db = $this->getConnection();

        $this->createTableForIndexAndConstraintTests($db, $tableName, $columnName);
        $db->createCommand()->addDefaultValue($tableName, $constraintName, $columnName, 919)->execute();

        /** @var DefaultValue[] $constraints */
        $constraints = $db->getSchema()->getTableDefaultValues($tableName, true);

        $this->assertIsArray($constraints);
        $this->assertCount(1, $constraints);
        $this->assertInstanceOf(DefaultValue::class, $constraints[0]);
        $this->assertEquals($constraintName, $constraints[0]->name);
        $this->assertEquals([$columnName], $constraints[0]->columnNames);
        $this->assertStringContainsString('919', $constraints[0]->value);

        $db->createCommand()->dropDefaultValue($tableName, $constraintName)->execute();

        $constraints = $db->getSchema()->getTableDefaultValues($tableName, true);

        $this->assertSame([], $constraints);

        $this->dropTableForIndexAndConstraintTests($db, $tableName);

        $db->close();
    }

    public function testWorkWithPrimaryKeyConstraint(): void
    {
        $tableName = 'test_table_with';
        $constraintName = 't_constraint';
        $columnName = 't_field';

        $db = $this->getConnection();

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

        $db->close();
    }

    #[DataProviderExternal(SchemaProvider::class, 'withIndexDataProvider')]
    public function testWorkWithIndex(
        ?string $indexType = null,
        ?string $indexMethod = null,
        ?string $columnType = null,
        bool $isPrimary = false,
        bool $isUnique = false
    ): void {
        $tableName = 'test_table_with';
        $indexName = 't_index';
        $columnName = 't_field';

        $db = $this->getConnection();
        $qb = $db->getQueryBuilder();

        $this->createTableForIndexAndConstraintTests($db, $tableName, $columnName, $columnType);

        $indexSql = $qb->createIndex($tableName, $indexName, $columnName, $indexType, $indexMethod);
        $db->createCommand($indexSql)->execute();

        $this->assertEquals(
            [new Index($indexName, [$columnName], $isUnique, $isPrimary)],
            $db->getSchema()->getTableIndexes($tableName),
        );

        $this->dropTableForIndexAndConstraintTests($db, $tableName);

        $db->close();
    }

    /**
     * @link https://github.com/yiisoft/db/issues/718
     */
    public function testIssue718(): void
    {
        $db = $this->getConnection();

        if ($db->getDriverName() === 'oci' || $db->getDriverName() === 'sqlite' || $db->getDriverName() === 'sqlsrv') {
            $this->markTestSkipped('Test is not supported by sqlite, oci and sqlsrv drivers.');
        }

        if ($db->getTableSchema('{{%table}}', true) !== null) {
            $db->createCommand()->dropTable('{{%table}}')->execute();
        }

        $db->createCommand()->createTable('{{%table}}', ['array' => 'json'])->execute();
        $db->createCommand()->insert('{{%table}}', ['array' => [1, 2]])->execute();

        $result = str_replace(' ', '', $db->createCommand('SELECT * FROM {{%table}}')->queryScalar());

        $this->assertSame('[1,2]', trim($result));
    }

    #[DataProviderExternal(SchemaProvider::class, 'resultColumns')]
    public function testGetResultColumn(ColumnInterface|null $expected, array $metadata): void
    {
        $db = $this->getConnection();
        $schema = $db->getSchema();

        $this->assertEquals($expected, $schema->getResultColumn($metadata));

        $db->close();
    }

    public function testPrimaryKeyOrder(): void
    {
        $db = $this->getConnection(true);
        $schema = $db->getSchema();

        $tableSchema = $schema->getTableSchema('order_item');

        $this->assertSame(['order_id', 'item_id'], $tableSchema->getPrimaryKey());

        $db->close();
    }

    protected function createTableForIndexAndConstraintTests(
        ConnectionInterface $db,
        string $tableName,
        string $columnName,
        ?string $columnType = null
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
}
