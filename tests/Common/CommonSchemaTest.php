<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Common;

use JsonException;
use PDO;
use Throwable;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Constraint\CheckConstraint;
use Yiisoft\Db\Constraint\Constraint;
use Yiisoft\Db\Constraint\DefaultValueConstraint;
use Yiisoft\Db\Constraint\ForeignKeyConstraint;
use Yiisoft\Db\Constraint\IndexConstraint;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidCallException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Schema\SchemaInterface;
use Yiisoft\Db\Schema\TableSchemaInterface;
use Yiisoft\Db\Tests\AbstractSchemaTest;
use Yiisoft\Db\Tests\Support\AnyCaseValue;
use Yiisoft\Db\Tests\Support\AnyValue;
use Yiisoft\Db\Tests\Support\DbHelper;

use function array_keys;
use function count;
use function gettype;
use function is_array;
use function is_object;
use function json_encode;
use function ksort;
use function mb_chr;
use function sort;
use function str_replace;
use function strtolower;

abstract class CommonSchemaTest extends AbstractSchemaTest
{
    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testColumnComment(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $schema = $db->getSchema();

        if ($schema->getTableSchema('testCommentTable') !== null) {
            $command->dropTable('testCommentTable')->execute();
        }

        $command->createTable('testCommentTable', ['bar' => SchemaInterface::TYPE_INTEGER,])->execute();
        $command->addCommentOnColumn('testCommentTable', 'bar', 'Test comment for column.')->execute();

        $this->assertSame(
            'Test comment for column.',
            $schema->getTableSchema('testCommentTable')->getColumn('bar')->getComment(),
        );

        $db->close();
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\SchemaProvider::columns
     */
    public function testColumnSchema(array $columns, string $tableName): void
    {
        $this->columnSchema($columns, $tableName);
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

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
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
            SchemaInterface::INDEX_UNIQUE,
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
            SchemaInterface::INDEX_UNIQUE,
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
            SchemaInterface::INDEX_UNIQUE,
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

    /**
     * @throws Exception
     * @throws InvalidCallException
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testGetPrimaryKey(): void
    {
        $db = $this->getConnection(true);

        $command = $db->createCommand();

        $insertResult = $command->insertWithReturningPks('animal', ['type' => 'cat']);
        $selectResult = $command->setSql(
            DbHelper::replaceQuotes(
                <<<SQL
                SELECT [[id]] FROM [[animal]] WHERE [[type]] = 'cat'
                SQL,
                $db->getDriverName(),
            )
        )->queryOne();

        $this->assertIsArray($insertResult);
        $this->assertIsArray($selectResult);
        $this->assertEquals($selectResult['id'], $insertResult['id']);

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
            $this->assertContainsOnlyInstancesOf(CheckConstraint::class, $checks);
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
            $this->assertContainsOnlyInstancesOf(DefaultValueConstraint::class, $defaultValues);
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
            $this->assertContainsOnlyInstancesOf(ForeignKeyConstraint::class, $foreignKeys);
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
            $this->assertContainsOnlyInstancesOf(IndexConstraint::class, $indexes);
        }

        $db->close();
    }

    public function testGetSchemaPrimaryKeys(): void
    {
        $db = $this->getConnection();

        $schema = $db->getSchema();
        $tablePks = $schema->getSchemaPrimaryKeys();

        $this->assertIsArray($tablePks);
        $this->assertContainsOnlyInstancesOf(Constraint::class, $tablePks);

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
            $this->assertContainsOnlyInstancesOf(Constraint::class, $uniques);
        }

        $db->close();
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\SchemaProvider::columnsTypeChar
     */
    public function testGetStringFieldsSize(
        string $columnName,
        string $columnType,
        int|null $columnSize,
        string $columnDbType
    ): void {
        $db = $this->getConnection(true);

        $schema = $db->getSchema();
        $tableSchema = $schema->getTableSchema('type');

        $this->assertInstanceOf(TableSchemaInterface::class, $tableSchema);

        $columns = $tableSchema->getColumns();

        foreach ($columns as $name => $column) {
            $type = $column->getType();
            $size = $column->getSize();
            $dbType = $column->getDbType();

            if ($name === $columnName) {
                $this->assertSame($columnType, $type);
                $this->assertSame($columnSize, $size);
                $this->assertSame($columnDbType, $dbType);
            }
        }

        $db->close();
    }

    public function testGetTableChecks(): void
    {
        $db = $this->getConnection(true);

        $schema = $db->getSchema();
        $tableChecks = $schema->getTableChecks('T_constraints_1');

        $this->assertIsArray($tableChecks);

        $this->assertContainsOnlyInstancesOf(CheckConstraint::class, $tableChecks);

        $db->close();
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\SchemaProvider::pdoAttributes
     *
     * @throws NotSupportedException
     */
    public function testGetTableNames(array $pdoAttributes): void
    {
        $db = $this->getConnection(true);

        foreach ($pdoAttributes as $name => $value) {
            if ($name === PDO::ATTR_EMULATE_PREPARES) {
                continue;
            }

            $db->getPDO()?->setAttribute($name, $value);
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

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\SchemaProvider::tableSchema
     */
    public function testGetTableSchema(string $name, string $expectedName): void
    {
        $db = $this->getConnection(true);

        $tableSchema = $db->getSchema()->getTableSchema($name);

        $this->assertInstanceOf(TableSchemaInterface::class, $tableSchema);
        $this->assertEquals($expectedName, $tableSchema->getName());

        $db->close();
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\SchemaProvider::pdoAttributes
     *
     * @throws NotSupportedException
     */
    public function testGetTableSchemas(array $pdoAttributes): void
    {
        $db = $this->getConnection(true);

        foreach ($pdoAttributes as $name => $value) {
            if ($name === PDO::ATTR_EMULATE_PREPARES) {
                continue;
            }

            $db->getPDO()?->setAttribute($name, $value);
        }

        $schema = $db->getSchema();
        $tables = $schema->getTableSchemas();

        $this->assertCount(count($schema->getTableNames()), $tables);

        foreach ($tables as $table) {
            $this->assertInstanceOf(TableSchemaInterface::class, $table);
        }

        $db->close();
    }

    /**
     * @throws InvalidConfigException
     * @throws NotSupportedException
     * @throws Exception
     */
    public function testGetTableSchemaWithAttrCase(): void
    {
        $db = $this->getConnection(true);

        $schema = $db->getSchema();
        $db->getActivePDO()->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);

        $this->assertCount(count($schema->getTableNames()), $schema->getTableSchemas());

        $db->getActivePDO()->setAttribute(PDO::ATTR_CASE, PDO::CASE_UPPER);

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

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
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

    /**
     * @depends testSchemaCache
     */
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

    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
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

    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
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

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\SchemaProvider::tableSchemaCachePrefixes
     */
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

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\SchemaProvider::constraints
     *
     * @throws Exception
     * @throws JsonException
     */
    public function testTableSchemaConstraints(string $tableName, string $type, mixed $expected): void
    {
        if ($expected === false) {
            $this->expectException(NotSupportedException::class);
        }

        $db = $this->getConnection(true);
        $schema = $db->getSchema();
        $constraints = $schema->{'getTable' . ucfirst($type)}($tableName);

        $this->assertMetadataEquals($expected, $constraints);

        $db->close();
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\SchemaProvider::constraints
     *
     * @throws Exception
     * @throws JsonException
     * @throws InvalidConfigException
     */
    public function testTableSchemaConstraintsWithPdoLowercase(string $tableName, string $type, mixed $expected): void
    {
        if ($expected === false) {
            $this->expectException(NotSupportedException::class);
        }

        $db = $this->getConnection(true);

        $schema = $db->getSchema();
        $db->getActivePDO()->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);
        $constraints = $schema->{'getTable' . ucfirst($type)}($tableName, true);

        $this->assertMetadataEquals($expected, $constraints);

        $db->close();
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\SchemaProvider::constraints
     *
     * @throws Exception
     * @throws JsonException
     * @throws InvalidConfigException
     */
    public function testTableSchemaConstraintsWithPdoUppercase(string $tableName, string $type, mixed $expected): void
    {
        if ($expected === false) {
            $this->expectException(NotSupportedException::class);
        }

        $db = $this->getConnection(true);

        $schema = $db->getSchema();
        $db->getActivePDO()->setAttribute(PDO::ATTR_CASE, PDO::CASE_UPPER);
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

    /**
     * @throws JsonException
     */
    protected function assertMetadataEquals($expected, $actual): void
    {
        switch (strtolower(gettype($expected))) {
            case 'object':
                $this->assertIsObject($actual);
                break;
            case 'array':
                $this->assertIsArray($actual);
                break;
            case 'null':
                $this->assertNull($actual);
                break;
        }

        if (is_array($expected)) {
            $this->normalizeArrayKeys($expected, false);
            $this->normalizeArrayKeys($actual, false);
        }

        $this->normalizeConstraints($expected, $actual);

        if (is_array($expected)) {
            $this->normalizeArrayKeys($expected, true);
            $this->normalizeArrayKeys($actual, true);
        }

        $this->assertEquals($expected, $actual);
    }

    protected function columnSchema(array $columns, string $table): void
    {
        $db = $this->getConnection(true);

        $table = $db->getTableSchema($table, true);

        $this->assertNotNull($table);

        $expectedColNames = array_keys($columns);
        sort($expectedColNames);
        $colNames = $table->getColumnNames();
        sort($colNames);

        $this->assertSame($expectedColNames, $colNames);

        foreach ($table->getColumns() as $name => $column) {
            $expected = $columns[$name];

            $this->assertSame(
                $expected['dbType'],
                $column->getDbType(),
                "dbType of column $name does not match. type is {$column->getType()}, dbType is {$column->getDbType()}."
            );
            $this->assertSame(
                $expected['phpType'],
                $column->getPhpType(),
                "phpType of column $name does not match. type is {$column->getType()}, dbType is {$column->getDbType()}."
            );
            $this->assertSame(
                $expected['primaryKey'],
                $column->isPrimaryKey(),
                "primaryKey of column $name does not match."
            );
            $this->assertSame($expected['type'], $column->getType(), "type of column $name does not match.");
            $this->assertSame(
                $expected['allowNull'],
                $column->isAllowNull(),
                "allowNull of column $name does not match."
            );
            $this->assertSame(
                $expected['autoIncrement'],
                $column->isAutoIncrement(),
                "autoIncrement of column $name does not match."
            );
            $this->assertSame(
                $expected['enumValues'],
                $column->getEnumValues(),
                "enumValues of column $name does not match."
            );
            $this->assertSame($expected['size'], $column->getSize(), "size of column $name does not match.");
            $this->assertSame(
                $expected['precision'],
                $column->getPrecision(),
                "precision of column $name does not match."
            );

            $this->assertSame($expected['scale'], $column->getScale(), "scale of column $name does not match.");

            if (is_object($expected['defaultValue'])) {
                $this->assertIsObject(
                    $column->getDefaultValue(),
                    "defaultValue of column $name is expected to be an object but it is not."
                );
                $this->assertSame(
                    (string) $expected['defaultValue'],
                    (string) $column->getDefaultValue(),
                    "defaultValue of column $name does not match."
                );
            } else {
                $this->assertSame(
                    $expected['defaultValue'],
                    $column->getDefaultValue(),
                    "defaultValue of column $name does not match."
                );
            }

            /* Pgsql only */
            if (isset($expected['dimension'])) {
                /** @psalm-suppress UndefinedMethod */
                $this->assertSame(
                    $expected['dimension'],
                    $column->getDimension(),
                    "dimension of column $name does not match"
                );
            }
        }

        $db->close();
    }

    /**
     * @throws JsonException
     */
    private function normalizeArrayKeys(array &$array, bool $caseSensitive): void
    {
        $newArray = [];

        foreach ($array as $value) {
            if ($value instanceof Constraint) {
                $key = (array) $value;
                unset(
                    $key["\000Yiisoft\Db\Constraint\Constraint\000name"],
                    $key["\u0000Yiisoft\\Db\\Constraint\\ForeignKeyConstraint\u0000foreignSchemaName"]
                );

                foreach ($key as $keyName => $keyValue) {
                    if ($keyValue instanceof AnyCaseValue) {
                        $key[$keyName] = $keyValue->value;
                    } elseif ($keyValue instanceof AnyValue) {
                        $key[$keyName] = '[AnyValue]';
                    }
                }

                ksort($key, SORT_STRING);
                $newArray[$caseSensitive
                    ? json_encode($key, JSON_THROW_ON_ERROR)
                    : strtolower(json_encode($key, JSON_THROW_ON_ERROR))] = $value;
            } else {
                $newArray[] = $value;
            }
        }

        ksort($newArray, SORT_STRING);
        $array = $newArray;
    }

    private function normalizeConstraints($expected, $actual): void
    {
        if (is_array($expected)) {
            foreach ($expected as $key => $value) {
                if (!$value instanceof Constraint || !isset($actual[$key]) || !$actual[$key] instanceof Constraint) {
                    continue;
                }

                $this->normalizeConstraintPair($value, $actual[$key]);
            }
        } elseif ($expected instanceof Constraint && $actual instanceof Constraint) {
            $this->normalizeConstraintPair($expected, $actual);
        }
    }

    private function normalizeConstraintPair(Constraint $expectedConstraint, Constraint $actualConstraint): void
    {
        if ($expectedConstraint::class !== $actualConstraint::class) {
            return;
        }

        foreach (array_keys((array) $expectedConstraint) as $name) {
            if ($expectedConstraint->getName() instanceof AnyValue) {
                $actualConstraint->name($expectedConstraint->getName());
            } elseif ($expectedConstraint->getName() instanceof AnyCaseValue) {
                $actualConstraintName = $actualConstraint->getName();

                $this->assertIsString($actualConstraintName);

                $actualConstraint->name(new AnyCaseValue($actualConstraintName));
            }
        }
    }

    public function testWorkWithUniqueConstraint(): void
    {
        $tableName = 'test_table_with';
        $constraintName = 't_constraint';
        $columnName = 't_field';

        $db = $this->getConnection();

        $this->createTableForIndexAndConstraintTests($db, $tableName, $columnName);
        $db->createCommand()->addUnique($tableName, $constraintName, $columnName)->execute();

        /** @var Constraint[] $constraints */
        $constraints = $db->getSchema()->getTableUniques($tableName, true);

        $this->assertIsArray($constraints);
        $this->assertCount(1, $constraints);
        $this->assertInstanceOf(Constraint::class, $constraints[0]);
        $this->assertEquals($constraintName, $constraints[0]->getName());
        $this->assertEquals([$columnName], $constraints[0]->getColumnNames());

        $db->createCommand()->dropUnique($tableName, $constraintName)->execute();

        $constraints = $db->getSchema()->getTableUniques($tableName, true);

        $this->assertIsArray($constraints);
        $this->assertCount(0, $constraints);

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

        /** @var CheckConstraint[] $constraints */
        $constraints = $db->getSchema()->getTableChecks($tableName, true);

        $this->assertIsArray($constraints);
        $this->assertCount(1, $constraints);
        $this->assertInstanceOf(CheckConstraint::class, $constraints[0]);
        $this->assertEquals($constraintName, $constraints[0]->getName());
        $this->assertEquals([$columnName], $constraints[0]->getColumnNames());
        $this->assertStringContainsString($columnName, $constraints[0]->getExpression());

        $db->createCommand()->dropCheck($tableName, $constraintName)->execute();

        $constraints = $db->getSchema()->getTableChecks($tableName, true);

        $this->assertIsArray($constraints);
        $this->assertCount(0, $constraints);

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

        /** @var DefaultValueConstraint[] $constraints */
        $constraints = $db->getSchema()->getTableDefaultValues($tableName, true);

        $this->assertIsArray($constraints);
        $this->assertCount(1, $constraints);
        $this->assertInstanceOf(DefaultValueConstraint::class, $constraints[0]);
        $this->assertEquals($constraintName, $constraints[0]->getName());
        $this->assertEquals([$columnName], $constraints[0]->getColumnNames());
        $this->assertStringContainsString('919', $constraints[0]->getValue());

        $db->createCommand()->dropDefaultValue($tableName, $constraintName)->execute();

        $constraints = $db->getSchema()->getTableDefaultValues($tableName, true);

        $this->assertIsArray($constraints);
        $this->assertCount(0, $constraints);

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

        $constraints = $db->getSchema()->getTablePrimaryKey($tableName, true);

        $this->assertInstanceOf(Constraint::class, $constraints);
        $this->assertEquals($constraintName, $constraints->getName());
        $this->assertEquals([$columnName], $constraints->getColumnNames());

        $db->createCommand()->dropPrimaryKey($tableName, $constraintName)->execute();

        $constraints = $db->getSchema()->getTablePrimaryKey($tableName, true);

        $this->assertNull($constraints);

        $this->dropTableForIndexAndConstraintTests($db, $tableName);

        $db->close();
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\SchemaProvider::withIndexDataProvider
     */
    public function testWorkWithIndex(
        string $indexType = null,
        string $indexMethod = null,
        string $columnType = null,
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

        /** @var IndexConstraint[] $indexes */
        $indexes = $db->getSchema()->getTableIndexes($tableName, true);
        $this->assertIsArray($indexes);
        $this->assertCount(1, $indexes);
        $this->assertInstanceOf(IndexConstraint::class, $indexes[0]);
        $this->assertEquals($indexName, $indexes[0]->getName());
        $this->assertEquals([$columnName], $indexes[0]->getColumnNames());
        $this->assertSame($isUnique, $indexes[0]->isUnique());
        $this->assertSame($isPrimary, $indexes[0]->isPrimary());

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

    protected function createTableForIndexAndConstraintTests(
        ConnectionInterface $db,
        string $tableName,
        string $columnName,
        string $columnType = null
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
