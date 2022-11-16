<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Common;

use PDO;
use Yiisoft\Db\Constraint\CheckConstraint;
use Yiisoft\Db\Constraint\Constraint;
use Yiisoft\Db\Constraint\DefaultValueConstraint;
use Yiisoft\Db\Constraint\ForeignKeyConstraint;
use Yiisoft\Db\Constraint\IndexConstraint;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Schema\Schema;
use Yiisoft\Db\Schema\TableSchemaInterface;
use Yiisoft\Db\Tests\AbstractSchemaTest;
use Yiisoft\Db\Tests\Support\AnyCaseValue;
use Yiisoft\Db\Tests\Support\AnyValue;
use Yiisoft\Db\Tests\Support\TestTrait;

use function array_keys;
use function count;
use function gettype;
use function is_array;
use function is_object;
use function json_encode;
use function ksort;
use function mb_chr;
use function sort;
use function strtolower;

/**
 * @group mssql
 * @group mysql
 * @group pgsql
 * @group oracle
 * @group sqlite
 */
abstract class CommonSchemaTest extends AbstractSchemaTest
{
    use TestTrait;

    public function testColumnSchema(array $columns): void
    {
        $db = $this->getConnectionWithData();

        $table = $db->getTableSchema('type', true);

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
    }

    public function testCompositeFk(): void
    {
        $db = $this->getConnection();

        $schema = $db->getSchema();
        $table = $schema->getTableSchema('composite_fk');

        $this->assertNotNull($table);

        $fk = $table->getForeignKeys();

        $this->assertCount(1, $fk);
        $this->assertTrue(isset($fk['FK_composite_fk_order_item']));
        $this->assertEquals('order_item', $fk['FK_composite_fk_order_item'][0]);
        $this->assertEquals('order_id', $fk['FK_composite_fk_order_item']['order_id']);
        $this->assertEquals('item_id', $fk['FK_composite_fk_order_item']['item_id']);
    }

    public function testContraintTablesExistance(): void
    {
        $db = $this->getConnectionWithData();

        $tableNames = ['T_constraints_1', 'T_constraints_2', 'T_constraints_3', 'T_constraints_4'];
        $schema = $db->getSchema();

        foreach ($tableNames as $tableName) {
            $tableSchema = $schema->getTableSchema($tableName);
            $this->assertInstanceOf(TableSchemaInterface::class, $tableSchema, $tableName);
        }
    }

    public function testFindUniquesIndex(): void
    {
        $db = $this->getConnectionWithData();

        $schema = $db->getSchema();
        $tUpsert = $schema->getTableSchema('T_upsert');

        $this->assertInstanceOf(TableSchemaInterface::class, $tUpsert);
        $this->assertContains([0 => 'email', 1 => 'recovery_email'], $schema->findUniqueIndexes($tUpsert));
        $this->assertContains([0 => 'email'], $schema->findUniqueIndexes($tUpsert));
    }

    public function testGetColumnNoExist(): void
    {
        $db = $this->getConnectionWithData();

        $schema = $db->getSchema();
        $table = $schema->getTableSchema('negative_default_values');

        $this->assertNotNull($table);
        $this->assertNull($table->getColumn('no_exist'));
    }

    public function testGetDefaultSchema(): void
    {
        $db = $this->getConnection();

        $schema = $db->getSchema();

        $this->assertNull($schema->getDefaultSchema());
    }

    public function testGetNonExistingTableSchema(): void
    {
        $db = $this->getConnection();

        $schema = $db->getSchema();

        $this->assertNull($schema->getTableSchema('nonexisting_table'));
    }

    public function testGetPrimaryKey(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();

        if ($db->getSchema()->getTableSchema('testPKTable') !== null) {
            $command->dropTable('testPKTable')->execute();
        }

        $command->createTable('testPKTable', ['id' => Schema::TYPE_PK, 'bar' => Schema::TYPE_INTEGER])->execute();
        $insertResult = $command->insertEx('testPKTable', ['bar' => 1]);
        $selectResult = $command->setSql('select [id] from [testPKTable] where [bar]=1')->queryOne();

        $this->assertIsArray($insertResult);
        $this->assertIsArray($selectResult);
        $this->assertEquals($selectResult['id'], $insertResult['id']);
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
    }

    public function testGetSchemaPrimaryKeys(): void
    {
        $db = $this->getConnection();

        $schema = $db->getSchema();
        $tablePks = $schema->getSchemaPrimaryKeys();

        $this->assertIsArray($tablePks);
        $this->assertContainsOnlyInstancesOf(Constraint::class, $tablePks);
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
    }

    public function testGetStringFieldsSize(): void
    {
        $db = $this->getConnectionWithData();

        $schema = $db->getSchema();
        $tableSchema = $schema->getTableSchema('type');

        $this->assertInstanceOf(TableSchemaInterface::class, $tableSchema);

        $columns = $tableSchema->getColumns();
        $expectedType = null;
        $expectedSize = null;
        $expectedDbType = null;

        foreach ($columns as $name => $column) {
            $type = $column->getType();
            $size = $column->getSize();
            $dbType = $column->getDbType();

            if (str_starts_with($name, 'char_')) {
                switch ($name) {
                    case 'char_col':
                        $expectedType = 'char';
                        $expectedSize = 100;
                        $expectedDbType = 'char(100)';
                        break;
                    case 'char_col2':
                        $expectedType = 'string';
                        $expectedSize = 100;
                        $expectedDbType = 'varchar(100)';
                        break;
                    case 'char_col3':
                        $expectedType = 'text';
                        $expectedSize = null;
                        $expectedDbType = 'text';
                        break;
                }

                $this->assertSame($expectedType, $type);
                $this->assertSame($expectedSize, $size);
                $this->assertSame($expectedDbType, $dbType);
            }
        }
    }

    public function testGetTableChecks(): void
    {
        $db = $this->getConnectionWithData();

        $schema = $db->getSchema();
        $tableChecks = $schema->getTableChecks('T_constraints_1');

        $this->assertIsArray($tableChecks);
        $this->assertContainsOnlyInstancesOf(CheckConstraint::class, $tableChecks);
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\SchemaProvider::pdoAttributes()
     */
    public function testGetTableNames(array $pdoAttributes): void
    {
        $db = $this->getConnectionWithData();

        foreach ($pdoAttributes as $name => $value) {
            if ($name === PDO::ATTR_EMULATE_PREPARES) {
                continue;
            }

            $db->getPDO()?->setAttribute($name, $value);
        }

        $schema = $db->getSchema();
        $tablesNames = $schema->getTableNames();
        $tablesNames = array_map(static fn ($item) => trim($item, '[]'), $tablesNames);

        $this->assertContains('customer', $tablesNames);
        $this->assertContains('category', $tablesNames);
        $this->assertContains('item', $tablesNames);
        $this->assertContains('order', $tablesNames);
        $this->assertContains('order_item', $tablesNames);
        $this->assertContains('type', $tablesNames);
        $this->assertContains('animal', $tablesNames);
        $this->assertContains('animal_view', $tablesNames);
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\SchemaProvider::tableSchema()
     */
    public function testGetTableSchema(string $name, string $expectedName): void
    {
        $db = $this->getConnectionWithData();

        $tableSchema = $db->getSchema()->getTableSchema($name);

        $this->assertInstanceOf(TableSchemaInterface::class, $tableSchema);
        $this->assertEquals($expectedName, $tableSchema->getName());
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\SchemaProvider::pdoAttributes()
     */
    public function testGetTableSchemas(array $pdoAttributes): void
    {
        $db = $this->getConnectionWithData();

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
    }

    public function testGetTableSchemasWithAttrCase(): void
    {
        $db = $this->getConnectionWithData();

        $schema = $db->getSchema();
        $db->getActivePDO()->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);

        $this->assertCount(count($schema->getTableNames()), $schema->getTableSchemas());

        $db->getActivePDO()->setAttribute(PDO::ATTR_CASE, PDO::CASE_UPPER);

        $this->assertCount(count($schema->getTableNames()), $schema->getTableSchemas());
    }

    public function testNegativeDefaultValues(): void
    {
        $schema = $this->getConnectionWithData();

        $schema = $schema->getSchema();
        $table = $schema->getTableSchema('negative_default_values');

        $this->assertNotNull($table);
        $this->assertSame(-123, $table->getColumn('tinyint_col')?->getDefaultValue());
        $this->assertSame(-123, $table->getColumn('smallint_col')?->getDefaultValue());
        $this->assertSame(-123, $table->getColumn('int_col')?->getDefaultValue());
        $this->assertSame(-123, $table->getColumn('bigint_col')?->getDefaultValue());
        $this->assertSame(-12345.6789, $table->getColumn('float_col')?->getDefaultValue());
        $this->assertEquals(-33.22, $table->getColumn('numeric_col')?->getDefaultValue());
    }

    public function testQuoterEscapingValue()
    {
        $db = $this->getConnectionWithData();

        $quoter = $db->getQuoter();
        $db->createCommand(
            <<<SQL
            DELETE FROM {{quoter}}
            SQL
        )->execute();
        $data = $this->generateQuoterEscapingValues();

        foreach ($data as $index => $value) {
            $quotedName = $quoter->quoteValue('testValue_' . $index);
            $quoteValue = $quoter->quoteValue($value);
            $db->createCommand(
                <<<SQL
                INSERT INTO {{quoter}} (name, description) VALUES ($quotedName, $quoteValue)
                SQL
            )->execute();
            $result = $db->createCommand(
                <<<SQL
                SELECT * FROM {{quoter}} WHERE name=$quotedName
                SQL
            )->queryOne();

            $this->assertSame($value, $result['description']);
        }
    }

    /**
     * @depends testSchemaCache
     */
    public function testRefreshTableSchema(): void
    {
        $db = $this->getConnectionWithData();

        $schema = $db->getSchema();
        $schemaCache = $this->getSchemaCache();

        $this->assertNotNull($schemaCache);

        $schema->schemaCacheEnable(true);
        $noCacheTable = $schema->getTableSchema('type', true);
        $schema->refreshTableSchema('type');
        $refreshedTable = $schema->getTableSchema('type');

        $this->assertNotSame($noCacheTable, $refreshedTable);
    }

    public function testSchemaCache(): void
    {
        $db = $this->getConnectionWithData();

        $schema = $db->getSchema();
        $schemaCache = $this->getSchemaCache();

        $this->assertNotNull($schemaCache);

        $schema->schemaCacheEnable(true);
        $noCacheTable = $schema->getTableSchema('type', true);
        $cachedTable = $schema->getTableSchema('type');

        $this->assertSame($noCacheTable, $cachedTable);

        $db->createCommand()->renameTable('type', 'type_test');
        $noCacheTable = $schema->getTableSchema('type', true);

        $this->assertNotSame($noCacheTable, $cachedTable);

        $db->createCommand()->renameTable('type_test', 'type');
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\SchemaProvider::tableSchemaCachePrefixes()
     */
    public function testTableSchemaCacheWithTablePrefixes(
        string $tablePrefix,
        string $tableName,
        string $testTablePrefix,
        string $testTableName
    ): void {
        $db = $this->getConnectionWithData();

        $schema = $db->getSchema();
        $schemaCache = $this->getSchemaCache();

        $this->assertNotNull($schemaCache);

        $schema->schemaCacheEnable(true);
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
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\SchemaProvider::constraints()
     *
     * @throws Exception
     */
    public function testTableSchemaConstraints(string $tableName, string $type, mixed $expected): void
    {
        if ($expected === false) {
            $this->expectException(NotSupportedException::class);
        }

        $db = $this->getConnectionWithData();
        $schema = $db->getSchema();
        $constraints = $schema->{'getTable' . ucfirst($type)}($tableName);

        $this->assertMetadataEquals($expected, $constraints);
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\SchemaProvider::constraints()
     *
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function testTableSchemaConstraintsWithPdoLowercase(string $tableName, string $type, mixed $expected): void
    {
        if ($expected === false) {
            $this->expectException(NotSupportedException::class);
        }

        $db = $this->getConnectionWithData();

        $schema = $db->getSchema();
        $db->getActivePDO()->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);
        $constraints = $schema->{'getTable' . ucfirst($type)}($tableName, true);

        $this->assertMetadataEquals($expected, $constraints);
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\SchemaProvider::constraints()
     *
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function testTableSchemaConstraintsWithPdoUppercase(string $tableName, string $type, mixed $expected): void
    {
        if ($expected === false) {
            $this->expectException(NotSupportedException::class);
        }

        $db = $this->getConnectionWithData();

        $schema = $db->getSchema();
        $db->getActivePDO()->setAttribute(PDO::ATTR_CASE, PDO::CASE_UPPER);
        $constraints = $schema->{'getTable' . ucfirst($type)}($tableName, true);

        $this->assertMetadataEquals($expected, $constraints);
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
}
