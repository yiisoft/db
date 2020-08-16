<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests;

use PDO;
use Yiisoft\Db\Constraint\CheckConstraint;
use Yiisoft\Db\Constraint\Constraint;
use Yiisoft\Db\Constraint\ForeignKeyConstraint;
use Yiisoft\Db\Constraint\IndexConstraint;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Schema\ColumnSchema;
use Yiisoft\Db\Schema\Schema;
use Yiisoft\Db\Schema\TableSchema;

abstract class SchemaTest extends DatabaseTestCase
{
    /**
     * @var string[]
     */
    protected array $expectedSchemas;

    public function pdoAttributesProvider(): array
    {
        return [
            [[PDO::ATTR_EMULATE_PREPARES => true]],
            [[PDO::ATTR_EMULATE_PREPARES => false]],
        ];
    }

    public function testGetSchemaNames()
    {
        /* @var $schema Schema */
        $schema = $this->getConnection()->getSchema();

        $schemas = $schema->getSchemaNames();

        $this->assertNotEmpty($schemas);

        foreach ($this->expectedSchemas as $schema) {
            $this->assertContains($schema, $schemas);
        }
    }

    /**
     * @dataProvider pdoAttributesProvider
     *
     * @param array $pdoAttributes
     */
    public function testGetTableNames($pdoAttributes): void
    {
        $connection = $this->getConnection(true, true, true);

        foreach ($pdoAttributes as $name => $value) {
            $connection->getPDO()->setAttribute($name, $value);
        }

        /* @var $schema Schema */
        $schema = $connection->getSchema();

        $tables = $schema->getTableNames();

        $this->assertTrue(\in_array('customer', $tables));
        $this->assertTrue(\in_array('category', $tables));
        $this->assertTrue(\in_array('item', $tables));
        $this->assertTrue(\in_array('order', $tables));
        $this->assertTrue(\in_array('order_item', $tables));
        $this->assertTrue(\in_array('type', $tables));
        $this->assertTrue(\in_array('animal', $tables));
        $this->assertTrue(\in_array('animal_view', $tables));
    }

    /**
     * @dataProvider pdoAttributesProvider
     *
     * @param array $pdoAttributes
     */
    public function testGetTableSchemas($pdoAttributes)
    {
        $connection = $this->getConnection();

        foreach ($pdoAttributes as $name => $value) {
            $connection->getPDO()->setAttribute($name, $value);
        }

        /* @var $schema Schema */
        $schema = $connection->getSchema();

        $tables = $schema->getTableSchemas();

        $this->assertEquals(\count($schema->getTableNames()), \count($tables));

        foreach ($tables as $table) {
            $this->assertInstanceOf(TableSchema::class, $table);
        }
    }

    public function testGetTableSchemasWithAttrCase()
    {
        $db = $this->getConnection(false);

        $db->getSlavePdo()->setAttribute(\PDO::ATTR_CASE, \PDO::CASE_LOWER);
        $this->assertEquals(\count($db->getSchema()->getTableNames()), \count($db->getSchema()->getTableSchemas()));

        $db->getSlavePdo()->setAttribute(\PDO::ATTR_CASE, \PDO::CASE_UPPER);
        $this->assertEquals(\count($db->getSchema()->getTableNames()), \count($db->getSchema()->getTableSchemas()));
    }

    public function testGetNonExistingTableSchema()
    {
        $this->assertNull($this->getConnection()->getSchema()->getTableSchema('nonexisting_table'));
    }

    public function testSchemaCache()
    {
        /* @var $db Connection */
        $db = $this->getConnection();

        /* @var $schema Schema */
        $schema = $db->getSchema();

        $schema->getDb()->setEnableSchemaCache(true);
        $schema->getDb()->setSchemaCache($this->cache);

        $noCacheTable = $schema->getTableSchema('type', true);
        $cachedTable = $schema->getTableSchema('type', false);

        $this->assertEquals($noCacheTable, $cachedTable);

        $db->createCommand()->renameTable('type', 'type_test');

        $noCacheTable = $schema->getTableSchema('type', true);

        $this->assertNotSame($noCacheTable, $cachedTable);

        $db->createCommand()->renameTable('type_test', 'type');
    }

    /**
     * @depends testSchemaCache
     */
    public function testRefreshTableSchema()
    {
        /* @var $schema Schema */
        $schema = $this->getConnection()->getSchema();

        $schema->getDb()->setEnableSchemaCache(true);
        $schema->getDb()->setSchemaCache($this->cache);

        $noCacheTable = $schema->getTableSchema('type', true);
        $schema->refreshTableSchema('type');
        $refreshedTable = $schema->getTableSchema('type', false);

        $this->assertNotSame($noCacheTable, $refreshedTable);
    }

    public function tableSchemaCachePrefixesProvider()
    {
        $configs = [
            [
                'prefix' => '',
                'name'   => 'type',
            ],
            [
                'prefix' => '',
                'name'   => '{{%type}}',
            ],
            [
                'prefix' => 'ty',
                'name'   => '{{%pe}}',
            ],
        ];
        $data = [];
        foreach ($configs as $config) {
            foreach ($configs as $testConfig) {
                if ($config === $testConfig) {
                    continue;
                }

                $description = sprintf(
                    "%s (with '%s' prefix) against %s (with '%s' prefix)",
                    $config['name'],
                    $config['prefix'],
                    $testConfig['name'],
                    $testConfig['prefix']
                );
                $data[$description] = [
                    $config['prefix'],
                    $config['name'],
                    $testConfig['prefix'],
                    $testConfig['name'],
                ];
            }
        }

        return $data;
    }

    /**
     * @dataProvider tableSchemaCachePrefixesProvider
     * @depends testSchemaCache
     */
    public function testTableSchemaCacheWithTablePrefixes($tablePrefix, $tableName, $testTablePrefix, $testTableName)
    {
        /* @var $schema Schema */
        $schema = $this->getConnection()->getSchema();

        $schema->getDb()->setEnableSchemaCache(true);
        $schema->getDb()->setSchemaCache($this->cache);
        $schema->getDb()->setTablePrefix($tablePrefix);
        $noCacheTable = $schema->getTableSchema($tableName, true);

        $this->assertInstanceOf(TableSchema::class, $noCacheTable);

        // Compare
        $schema->getDb()->setTablePrefix($testTablePrefix);
        $testNoCacheTable = $schema->getTableSchema($testTableName);

        $this->assertSame($noCacheTable, $testNoCacheTable);

        $schema->getDb()->setTablePrefix($tablePrefix);
        $schema->refreshTableSchema($tableName);
        $refreshedTable = $schema->getTableSchema($tableName, false);

        $this->assertInstanceOf(TableSchema::class, $refreshedTable);
        $this->assertNotSame($noCacheTable, $refreshedTable);

        // Compare
        $schema->getDb()->setTablePrefix($testTablePrefix);
        $schema->refreshTableSchema($testTablePrefix);
        $testRefreshedTable = $schema->getTableSchema($testTableName, false);

        $this->assertInstanceOf(TableSchema::class, $testRefreshedTable);
        $this->assertEquals($refreshedTable, $testRefreshedTable);
        $this->assertNotSame($testNoCacheTable, $testRefreshedTable);
    }

    public function testCompositeFk()
    {
        /* @var $schema Schema */
        $schema = $this->getConnection()->getSchema();

        $table = $schema->getTableSchema('composite_fk');

        $fk = $table->getForeignKeys();
        $this->assertCount(1, $fk);
        $this->assertTrue(isset($fk['FK_composite_fk_order_item']));
        $this->assertEquals('order_item', $fk['FK_composite_fk_order_item'][0]);
        $this->assertEquals('order_id', $fk['FK_composite_fk_order_item']['order_id']);
        $this->assertEquals('item_id', $fk['FK_composite_fk_order_item']['item_id']);
    }

    public function testGetPDOType()
    {
        $values = [
            [null, \PDO::PARAM_NULL],
            ['', \PDO::PARAM_STR],
            ['hello', \PDO::PARAM_STR],
            [0, \PDO::PARAM_INT],
            [1, \PDO::PARAM_INT],
            [1337, \PDO::PARAM_INT],
            [true, \PDO::PARAM_BOOL],
            [false, \PDO::PARAM_BOOL],
            [$fp = fopen(__FILE__, 'rb'), \PDO::PARAM_LOB],
        ];

        /* @var $schema Schema */
        $schema = $this->getConnection()->getSchema();

        foreach ($values as $value) {
            $this->assertEquals($value[1], $schema->getPdoType($value[0]), 'type for value ' . print_r($value[0], true) . ' does not match.');
        }
        fclose($fp);
    }

    public function getExpectedColumns()
    {
        return [
            'int_col' => [
                'type' => 'integer',
                'dbType' => 'int(11)',
                'phpType' => 'integer',
                'allowNull' => false,
                'autoIncrement' => false,
                'enumValues' => null,
                'size' => 11,
                'precision' => 11,
                'scale' => null,
                'defaultValue' => null,
            ],
            'int_col2' => [
                'type' => 'integer',
                'dbType' => 'int(11)',
                'phpType' => 'integer',
                'allowNull' => true,
                'autoIncrement' => false,
                'enumValues' => null,
                'size' => 11,
                'precision' => 11,
                'scale' => null,
                'defaultValue' => 1,
            ],
            'tinyint_col' => [
                'type' => 'tinyint',
                'dbType' => 'tinyint(3)',
                'phpType' => 'integer',
                'allowNull' => true,
                'autoIncrement' => false,
                'enumValues' => null,
                'size' => 3,
                'precision' => 3,
                'scale' => null,
                'defaultValue' => 1,
            ],
            'smallint_col' => [
                'type' => 'smallint',
                'dbType' => 'smallint(1)',
                'phpType' => 'integer',
                'allowNull' => true,
                'autoIncrement' => false,
                'enumValues' => null,
                'size' => 1,
                'precision' => 1,
                'scale' => null,
                'defaultValue' => 1,
            ],
            'char_col' => [
                'type' => 'char',
                'dbType' => 'char(100)',
                'phpType' => 'string',
                'allowNull' => false,
                'autoIncrement' => false,
                'enumValues' => null,
                'size' => 100,
                'precision' => 100,
                'scale' => null,
                'defaultValue' => null,
            ],
            'char_col2' => [
                'type' => 'string',
                'dbType' => 'varchar(100)',
                'phpType' => 'string',
                'allowNull' => true,
                'autoIncrement' => false,
                'enumValues' => null,
                'size' => 100,
                'precision' => 100,
                'scale' => null,
                'defaultValue' => 'something',
            ],
            'char_col3' => [
                'type' => 'text',
                'dbType' => 'text',
                'phpType' => 'string',
                'allowNull' => true,
                'autoIncrement' => false,
                'enumValues' => null,
                'size' => null,
                'precision' => null,
                'scale' => null,
                'defaultValue' => null,
            ],
            'enum_col' => [
                'type' => 'string',
                'dbType' => "enum('a','B','c,D')",
                'phpType' => 'string',
                'allowNull' => true,
                'autoIncrement' => false,
                'enumValues' => ['a', 'B', 'c,D'],
                'size' => null,
                'precision' => null,
                'scale' => null,
                'defaultValue' => null,
            ],
            'float_col' => [
                'type' => 'double',
                'dbType' => 'double(4,3)',
                'phpType' => 'double',
                'allowNull' => false,
                'autoIncrement' => false,
                'enumValues' => null,
                'size' => 4,
                'precision' => 4,
                'scale' => 3,
                'defaultValue' => null,
            ],
            'float_col2' => [
                'type' => 'double',
                'dbType' => 'double',
                'phpType' => 'double',
                'allowNull' => true,
                'autoIncrement' => false,
                'enumValues' => null,
                'size' => null,
                'precision' => null,
                'scale' => null,
                'defaultValue' => 1.23,
            ],
            'blob_col' => [
                'type' => 'binary',
                'dbType' => 'blob',
                'phpType' => 'resource',
                'allowNull' => true,
                'autoIncrement' => false,
                'enumValues' => null,
                'size' => null,
                'precision' => null,
                'scale' => null,
                'defaultValue' => null,
            ],
            'numeric_col' => [
                'type' => 'decimal',
                'dbType' => 'decimal(5,2)',
                'phpType' => 'string',
                'allowNull' => true,
                'autoIncrement' => false,
                'enumValues' => null,
                'size' => 5,
                'precision' => 5,
                'scale' => 2,
                'defaultValue' => '33.22',
            ],
            'time' => [
                'type' => 'timestamp',
                'dbType' => 'timestamp',
                'phpType' => 'string',
                'allowNull' => false,
                'autoIncrement' => false,
                'enumValues' => null,
                'size' => null,
                'precision' => null,
                'scale' => null,
                'defaultValue' => '2002-01-01 00:00:00',
            ],
            'bool_col' => [
                'type' => 'boolean',
                'dbType' => 'tinyint(1)',
                'phpType' => 'boolean',
                'allowNull' => false,
                'autoIncrement' => false,
                'enumValues' => null,
                'size' => 1,
                'precision' => 1,
                'scale' => null,
                'defaultValue' => null,
            ],
            'bool_col2' => [
                'type' => 'boolean',
                'dbType' => 'tinyint(1)',
                'phpType' => 'boolean',
                'allowNull' => true,
                'autoIncrement' => false,
                'enumValues' => null,
                'size' => 1,
                'precision' => 1,
                'scale' => null,
                'defaultValue' => true,
            ],
            'ts_default' => [
                'type' => 'timestamp',
                'dbType' => 'timestamp',
                'phpType' => 'string',
                'allowNull' => false,
                'autoIncrement' => false,
                'enumValues' => null,
                'size' => null,
                'precision' => null,
                'scale' => null,
                'defaultValue' => new Expression('CURRENT_TIMESTAMP'),
            ],
            'bit_col' => [
                'type' => 'integer',
                'dbType' => 'bit(8)',
                'phpType' => 'integer',
                'allowNull' => false,
                'autoIncrement' => false,
                'enumValues' => null,
                'size' => 8,
                'precision' => 8,
                'scale' => null,
                'defaultValue' => 130 //b '10000010'
            ],
            'json_col' => [
                'type' => 'json',
                'dbType' => 'json',
                'phpType' => 'array',
                'allowNull' => true,
                'autoIncrement' => false,
                'enumValues' => null,
                'size' => null,
                'precision' => null,
                'scale' => null,
                'defaultValue' => null,
            ],
        ];
    }

    public function testNegativeDefaultValues()
    {
        /* @var $schema Schema */
        $schema = $this->getConnection()->getSchema();

        $table = $schema->getTableSchema('negative_default_values');

        $this->assertEquals(-123, $table->getColumn('tinyint_col')->getDefaultValue());
        $this->assertEquals(-123, $table->getColumn('smallint_col')->getDefaultValue());
        $this->assertEquals(-123, $table->getColumn('int_col')->getDefaultValue());
        $this->assertEquals(-123, $table->getColumn('bigint_col')->getDefaultValue());
        $this->assertEquals(-12345.6789, $table->getColumn('float_col')->getDefaultValue());
        $this->assertEquals(-33.22, $table->getColumn('numeric_col')->getDefaultValue());
    }

    public function testColumnSchema()
    {
        $columns = $this->getExpectedColumns();

        $table = $this->getConnection(false)->getSchema()->getTableSchema('type', true);

        $expectedColNames = \array_keys($columns);

        sort($expectedColNames);

        $colNames = $table->getColumnNames();

        sort($colNames);

        $this->assertEquals($expectedColNames, $colNames);

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
            if (\is_object($expected['defaultValue'])) {
                $this->assertIsObject(
                    $column->getDefaultValue(),
                    "defaultValue of column $name is expected to be an object but it is not."
                );
                $this->assertEquals(
                    (string) $expected['defaultValue'],
                    (string) $column->getDefaultValue(),
                    "defaultValue of column $name does not match."
                );
            } else {
                $this->assertEquals(
                    $expected['defaultValue'],
                    $column->getDefaultValue(),
                    "defaultValue of column $name does not match."
                );
            }
            if (isset($expected['dimension'])) { // PgSQL only
                $this->assertSame(
                    $expected['dimension'],
                    $column->getDimension(),
                    "dimension of column $name does not match"
                );
            }
        }
    }

    public function testColumnSchemaDbTypecastWithEmptyCharType()
    {
        $columnSchema = new ColumnSchema();

        $columnSchema->setType(Schema::TYPE_CHAR);

        $this->assertSame('', $columnSchema->dbTypecast(''));
    }

    public function testFindUniqueIndexes()
    {
        $db = $this->getConnection();

        try {
            $db->createCommand()->dropTable('uniqueIndex')->execute();
        } catch (\Exception $e) {
        }

        $db->createCommand()->createTable('uniqueIndex', [
            'somecol'  => 'string',
            'someCol2' => 'string',
        ])->execute();

        /* @var $schema Schema */
        $schema = $db->getSchema();

        $uniqueIndexes = $schema->findUniqueIndexes($schema->getTableSchema('uniqueIndex', true));
        $this->assertEquals([], $uniqueIndexes);

        $db->createCommand()->createIndex('somecolUnique', 'uniqueIndex', 'somecol', true)->execute();

        $uniqueIndexes = $schema->findUniqueIndexes($schema->getTableSchema('uniqueIndex', true));
        $this->assertEquals([
            'somecolUnique' => ['somecol'],
        ], $uniqueIndexes);

        // create another column with upper case letter that fails postgres
        // see https://github.com/yiisoft/yii2/issues/10613
        $db->createCommand()->createIndex('someCol2Unique', 'uniqueIndex', 'someCol2', true)->execute();

        $uniqueIndexes = $schema->findUniqueIndexes($schema->getTableSchema('uniqueIndex', true));
        $this->assertEquals([
            'somecolUnique'  => ['somecol'],
            'someCol2Unique' => ['someCol2'],
        ], $uniqueIndexes);

        // see https://github.com/yiisoft/yii2/issues/13814
        $db->createCommand()->createIndex('another unique index', 'uniqueIndex', 'someCol2', true)->execute();

        $uniqueIndexes = $schema->findUniqueIndexes($schema->getTableSchema('uniqueIndex', true));
        $this->assertEquals([
            'somecolUnique'        => ['somecol'],
            'someCol2Unique'       => ['someCol2'],
            'another unique index' => ['someCol2'],
        ], $uniqueIndexes);
    }

    public function testContraintTablesExistance()
    {
        $tableNames = [
            'T_constraints_1',
            'T_constraints_2',
            'T_constraints_3',
            'T_constraints_4',
        ];

        $schema = $this->getConnection()->getSchema();

        foreach ($tableNames as $tableName) {
            $tableSchema = $schema->getTableSchema($tableName);
            $this->assertInstanceOf(TableSchema::class, $tableSchema, $tableName);
        }
    }

    public function constraintsProvider()
    {
        return [
            '1: index' => [
                'T_constraints_1',
                'indexes',
                [
                    $this->indexConstraint(AnyValue::getInstance(), ['C_id'], true, true),
                    $this->indexConstraint('CN_unique', ['C_unique'], false, true)
                ]
            ],
            '1: check' => [
                'T_constraints_1',
                'checks',
                [
                    $this->checkConstraint(AnyValue::getInstance(), "C_check <> ''", ['C_check'])
                ]
            ],
            '1: primary key' => [
                'T_constraints_1',
                'primaryKey',
                $this->constraint(AnyValue::getInstance(), ['C_id'])
            ],
            '1: unique' => [
                'T_constraints_1',
                'uniques',
                [
                    $this->constraint('CN_unique', ['C_unique'])
                ],
            ],
            '1: default' => ['T_constraints_1', 'defaultValues', false],
            '2: primary key' => [
                'T_constraints_2',
                'primaryKey',
                $this->constraint('CN_pk', ['C_id_1', 'C_id_2'])
            ],
            '2: unique' => [
                'T_constraints_2',
                'uniques',
                [
                    $this->constraint('CN_constraints_2_multi', ['C_index_2_1', 'C_index_2_2'])
                ]
            ],
            '2: index' => [
                'T_constraints_2',
                'indexes',
                [
                    $this->indexConstraint(AnyValue::getInstance(), ['C_id_1', 'C_id_2'], true, true),
                    $this->indexConstraint('CN_constraints_2_single', ['C_index_1'], false, false),
                    $this->indexConstraint('CN_constraints_2_multi', ['C_index_2_1', 'C_index_2_2'], false, true),
                ]
            ],
            '2: check' => ['T_constraints_2', 'checks', []],
            '2: default' => ['T_constraints_2', 'defaultValues', false],
            '3: index' => [
                'T_constraints_3',
                'indexes',
                [
                    $this->indexConstraint('CN_constraints_3', ['C_fk_id_1', 'C_fk_id_2'], false, false)
                ]
            ],
            '3: primary key' => ['T_constraints_3', 'primaryKey', null],
            '3: unique' => ['T_constraints_3', 'uniques', []],
            '3: check' => ['T_constraints_3', 'checks', []],
            '3: default' => ['T_constraints_3', 'defaultValues', false],
            '3: foreign key' => [
                'T_constraints_3',
                'foreignKeys',
                [
                    $this->foreignKeyConstraint(
                        'CN_constraints_3',
                        'T_constraints_2',
                        'CASCADE',
                        'CASCADE',
                        ['C_fk_id_1', 'C_fk_id_2'],
                        ['C_id_1', 'C_id_2']
                    )
                ]
            ],
            '4: primary key' => [
                'T_constraints_4',
                'primaryKey',
                $this->constraint(AnyValue::getInstance(), ['C_id'])
            ],
            '4: unique' => [
                'T_constraints_4',
                'uniques',
                [
                    $this->constraint('CN_constraints_4', ['C_col_1', 'C_col_2'])
                ]
            ],
            '4: check' => ['T_constraints_4', 'checks', []],
            '4: default' => ['T_constraints_4', 'defaultValues', false],
        ];
    }

    public function lowercaseConstraintsProvider()
    {
        return $this->constraintsProvider();
    }

    public function uppercaseConstraintsProvider()
    {
        return $this->constraintsProvider();
    }

    /**
     * @dataProvider constraintsProvider
     *
     * @param string $tableName
     * @param string $type
     * @param mixed  $expected
     */
    public function testTableSchemaConstraints($tableName, $type, $expected)
    {
        if ($expected === false) {
            $this->expectException(NotSupportedException::class);
        }

        $constraints = $this->getConnection(false)->getSchema()->{'getTable' . ucfirst($type)}($tableName);

        $this->assertMetadataEquals($expected, $constraints);
    }

    /**
     * @dataProvider uppercaseConstraintsProvider
     *
     * @param string $tableName
     * @param string $type
     * @param mixed  $expected
     */
    public function testTableSchemaConstraintsWithPdoUppercase($tableName, $type, $expected)
    {
        if ($expected === false) {
            $this->expectException(NotSupportedException::class);
        }

        $connection = $this->getConnection(false);
        $connection->getSlavePdo()->setAttribute(PDO::ATTR_CASE, PDO::CASE_UPPER);
        $constraints = $connection->getSchema()->{'getTable' . ucfirst($type)}($tableName, true);
        $this->assertMetadataEquals($expected, $constraints);
    }

    /**
     * @dataProvider lowercaseConstraintsProvider
     *
     * @param string $tableName
     * @param string $type
     * @param mixed  $expected
     */
    public function testTableSchemaConstraintsWithPdoLowercase($tableName, $type, $expected)
    {
        if ($expected === false) {
            $this->expectException(NotSupportedException::class);
        }

        $connection = $this->getConnection(false);

        $connection->getSlavePdo()->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);

        $constraints = $connection->getSchema()->{'getTable' . ucfirst($type)}($tableName, true);

        $this->assertMetadataEquals($expected, $constraints);
    }

    private function assertMetadataEquals($expected, $actual)
    {
        switch (strtolower(\gettype($expected))) {
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

        if (\is_array($expected)) {
            $this->normalizeArrayKeys($expected, false);
            $this->normalizeArrayKeys($actual, false);
        }

        $this->normalizeConstraints($expected, $actual);

        if (\is_array($expected)) {
            $this->normalizeArrayKeys($expected, true);
            $this->normalizeArrayKeys($actual, true);
        }

        $this->assertEquals($expected, $actual);
    }

    private function constraint($name, array $columnNames = []): Constraint
    {
        $ct = (new Constraint())
            ->name($name)
            ->columnNames($columnNames);

        return $ct;
    }

    private function checkConstraint($name, string $expression, array $columnNames = []): CheckConstraint
    {
        $cht = (new CheckConstraint())
            ->name($name)
            ->columnNames($columnNames)
            ->expression($expression);

        return $cht;
    }

    private function foreignKeyConstraint(
        $name,
        string $foreignTableName,
        string $onDelete,
        string $onUpdate,
        array $columnNames = [],
        array $foreignColumnNames = []
    ): ForeignKeyConstraint {
        $fk = (new ForeignKeyConstraint())
            ->name($name)
            ->columnNames($columnNames)
            ->foreignTableName($foreignTableName)
            ->foreignColumnNames($foreignColumnNames)
            ->onUpdate($onUpdate)
            ->onDelete($onDelete);

        return $fk;
    }

    private function indexConstraint($name, array $columnNames = [], bool $isPrimary = false, bool $isUnique = false): IndexConstraint
    {
        $ic = (new IndexConstraint())
            ->name($name)
            ->columnNames($columnNames)
            ->unique($isUnique)
            ->primary($isPrimary);

        return $ic;
    }

    private function normalizeArrayKeys(array &$array, $caseSensitive)
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
                $newArray[$caseSensitive ? json_encode($key) : strtolower(json_encode($key))] = $value;
            } else {
                $newArray[] = $value;
            }
        }

        ksort($newArray, SORT_STRING);

        $array = $newArray;
    }

    private function normalizeConstraints(&$expected, &$actual)
    {
        if (\is_array($expected)) {
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

    private function normalizeConstraintPair(Constraint $expectedConstraint, Constraint $actualConstraint)
    {
        if (get_class($expectedConstraint) !== get_class($actualConstraint)) {
            return;
        }

        foreach (array_keys((array) $expectedConstraint) as $name) {
            if ($expectedConstraint->getName() instanceof AnyValue) {
                $actualConstraint->name($expectedConstraint->getName());
            } elseif ($expectedConstraint->getName() instanceof AnyCaseValue) {
                $actualConstraint->name(new AnyCaseValue($actualConstraint->getName()));
            }
        }
    }
}
