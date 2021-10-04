<?php

declare(strict_types=1);

namespace Yiisoft\Db\TestUtility;

use PDO;
use Yiisoft\Db\Constraint\CheckConstraint;
use Yiisoft\Db\Constraint\Constraint;
use Yiisoft\Db\Constraint\ForeignKeyConstraint;
use Yiisoft\Db\Constraint\IndexConstraint;
use Yiisoft\Db\Schema\ColumnSchema;
use Yiisoft\Db\Schema\Schema;
use Yiisoft\Db\Schema\TableSchema;

use function array_keys;
use function fclose;
use function fopen;
use function gettype;
use function is_array;
use function json_encode;
use function ksort;
use function print_r;
use function sort;
use function sprintf;
use function strtolower;

trait TestSchemaTrait
{
    public function testGetTableSchemasWithAttrCase(): void
    {
        $db = $this->getConnection(false);

        $db->getSlavePdo()->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);

        $this->assertCount(count($db->getSchema()->getTableNames()), $db->getSchema()->getTableSchemas());

        $db->getSlavePdo()->setAttribute(PDO::ATTR_CASE, PDO::CASE_UPPER);

        $this->assertCount(count($db->getSchema()->getTableNames()), $db->getSchema()->getTableSchemas());
    }

    public function testGetNonExistingTableSchema(): void
    {
        $this->assertNull($this->getConnection()->getSchema()->getTableSchema('nonexisting_table'));
    }

    public function testSchemaCache(): void
    {
        $db = $this->getConnection();

        $schema = $db->getSchema();

        $this->schemaCache->setEnable(true);

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
    public function testRefreshTableSchema(): void
    {
        $schema = $this->getConnection()->getSchema();

        $this->schemaCache->setEnable(true);

        $noCacheTable = $schema->getTableSchema('type', true);

        $schema->refreshTableSchema('type');

        $refreshedTable = $schema->getTableSchema('type', false);

        $this->assertNotSame($noCacheTable, $refreshedTable);
    }

    public function testCompositeFk(): void
    {
        $schema = $this->getConnection()->getSchema();

        $table = $schema->getTableSchema('composite_fk');

        $fk = $table->getForeignKeys();

        $this->assertCount(1, $fk);
        $this->assertTrue(isset($fk['FK_composite_fk_order_item']));
        $this->assertEquals('order_item', $fk['FK_composite_fk_order_item'][0]);
        $this->assertEquals('order_id', $fk['FK_composite_fk_order_item']['order_id']);
        $this->assertEquals('item_id', $fk['FK_composite_fk_order_item']['item_id']);
    }

    public function testGetPDOType(): void
    {
        $values = [
            [null, PDO::PARAM_NULL],
            ['', PDO::PARAM_STR],
            ['hello', PDO::PARAM_STR],
            [0, PDO::PARAM_INT],
            [1, PDO::PARAM_INT],
            [1337, PDO::PARAM_INT],
            [true, PDO::PARAM_BOOL],
            [false, PDO::PARAM_BOOL],
            [$fp = fopen(__FILE__, 'rb'), PDO::PARAM_LOB],
        ];

        $schema = $this->getConnection()->getSchema();

        foreach ($values as $value) {
            $this->assertEquals($value[1], $schema->getPdoType($value[0]), 'type for value ' . print_r($value[0], true) . ' does not match.');
        }

        fclose($fp);
    }

    public function testColumnSchema(): void
    {
        $columns = $this->getExpectedColumns();

        $table = $this->getConnection(false)->getSchema()->getTableSchema('type', true);

        $expectedColNames = array_keys($columns);

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
            /* Pgsql only */
            if (isset($expected['dimension'])) {
                $this->assertSame(
                    $expected['dimension'],
                    $column->getDimension(),
                    "dimension of column $name does not match"
                );
            }
        }
    }

    public function testColumnSchemaDbTypecastWithEmptyCharType(): void
    {
        $columnSchema = new ColumnSchema();

        $columnSchema->setType(Schema::TYPE_CHAR);

        $this->assertSame('', $columnSchema->dbTypecast(''));
    }

    public function testNegativeDefaultValues(): void
    {
        $schema = $this->getConnection()->getSchema();

        $table = $schema->getTableSchema('negative_default_values');

        $this->assertEquals(-123, $table->getColumn('tinyint_col')->getDefaultValue());
        $this->assertEquals(-123, $table->getColumn('smallint_col')->getDefaultValue());
        $this->assertEquals(-123, $table->getColumn('int_col')->getDefaultValue());
        $this->assertEquals(-123, $table->getColumn('bigint_col')->getDefaultValue());
        $this->assertEquals(-12345.6789, $table->getColumn('float_col')->getDefaultValue());
        $this->assertEquals(-33.22, $table->getColumn('numeric_col')->getDefaultValue());
    }

    public function testContraintTablesExistance(): void
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

    public function testGetColumnNoExist(): void
    {
        $schema = $this->getConnection()->getSchema();
        $table = $schema->getTableSchema('negative_default_values');

        $this->assertNull($table->getColumn('no_exist'));
    }

    private function assertMetadataEquals($expected, $actual): void
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

    private function normalizeConstraints(&$expected, &$actual): void
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

    public function constraintsProviderTrait(): array
    {
        return [
            '1: primary key' => [
                'T_constraints_1',
                'primaryKey',
                (new Constraint())
                    ->name(AnyValue::getInstance())
                    ->columnNames(['C_id']),
            ],
            '1: check' => [
                'T_constraints_1',
                'checks',
                [
                    (new CheckConstraint())
                        ->name(AnyValue::getInstance())
                        ->columnNames(['C_check'])
                        ->expression("C_check <> ''"),
                ],
            ],
            '1: unique' => [
                'T_constraints_1',
                'uniques',
                [
                    (new Constraint())
                        ->name('CN_unique')
                        ->columnNames(['C_unique']),
                ],
            ],
            '1: index' => [
                'T_constraints_1',
                'indexes',
                [
                    (new IndexConstraint())
                        ->name(AnyValue::getInstance())
                        ->columnNames(['C_id'])
                        ->unique(true)
                        ->primary(true),
                    (new IndexConstraint())
                        ->name('CN_unique')
                        ->columnNames(['C_unique'])
                        ->primary(false)
                        ->unique(true),
                ],
            ],
            '1: default' => ['T_constraints_1', 'defaultValues', false],

            '2: primary key' => [
                'T_constraints_2',
                'primaryKey',
                (new Constraint())
                    ->name('CN_pk')
                    ->columnNames(['C_id_1', 'C_id_2']),
            ],
            '2: unique' => [
                'T_constraints_2',
                'uniques',
                [
                    (new Constraint())
                        ->name('CN_constraints_2_multi')
                        ->columnNames(['C_index_2_1', 'C_index_2_2']),
                ],
            ],
            '2: index' => [
                'T_constraints_2',
                'indexes',
                [
                    (new IndexConstraint())
                        ->name(AnyValue::getInstance())
                        ->columnNames(['C_id_1', 'C_id_2'])
                        ->unique(true)
                        ->primary(true),
                    (new IndexConstraint())
                        ->name('CN_constraints_2_single')
                        ->columnNames(['C_index_1'])
                        ->primary(false)
                        ->unique(false),
                    (new IndexConstraint())
                        ->name('CN_constraints_2_multi')
                        ->columnNames(['C_index_2_1', 'C_index_2_2'])
                        ->primary(false)
                        ->unique(true),
                ],
            ],
            '2: check' => ['T_constraints_2', 'checks', []],
            '2: default' => ['T_constraints_2', 'defaultValues', false],

            '3: primary key' => ['T_constraints_3', 'primaryKey', null],
            '3: foreign key' => [
                'T_constraints_3',
                'foreignKeys',
                [
                    (new ForeignKeyConstraint())
                        ->name('CN_constraints_3')
                        ->columnNames(['C_fk_id_1', 'C_fk_id_2'])
                        ->foreignTableName('T_constraints_2')
                        ->foreignColumnNames(['C_id_1', 'C_id_2'])
                        ->onDelete('CASCADE')
                        ->onUpdate('CASCADE'),
                ],
            ],
            '3: unique' => ['T_constraints_3', 'uniques', []],
            '3: index' => [
                'T_constraints_3',
                'indexes',
                [
                    (new IndexConstraint())
                        ->name('CN_constraints_3')
                        ->columnNames(['C_fk_id_1', 'C_fk_id_2'])
                        ->unique(false)
                        ->primary(false),
                ],
            ],
            '3: check' => ['T_constraints_3', 'checks', []],
            '3: default' => ['T_constraints_3', 'defaultValues', false],

            '4: primary key' => [
                'T_constraints_4',
                'primaryKey',
                (new Constraint())
                    ->name(AnyValue::getInstance())
                    ->columnNames(['C_id']),
            ],
            '4: unique' => [
                'T_constraints_4',
                'uniques',
                [
                    (new Constraint())
                        ->name('CN_constraints_4')
                        ->columnNames(['C_col_1', 'C_col_2']),
                ],
            ],
            '4: check' => ['T_constraints_4', 'checks', []],
            '4: default' => ['T_constraints_4', 'defaultValues', false],
        ];
    }

    public function pdoAttributesProviderTrait(): array
    {
        return [
            [[PDO::ATTR_EMULATE_PREPARES => true]],
            [[PDO::ATTR_EMULATE_PREPARES => false]],
        ];
    }

    public function tableSchemaCachePrefixesProviderTrait(): array
    {
        $configs = [
            [
                'prefix' => '',
                'name' => 'type',
            ],
            [
                'prefix' => '',
                'name' => '{{%type}}',
            ],
            [
                'prefix' => 'ty',
                'name' => '{{%pe}}',
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

    public function lowercaseConstraintsProviderTrait(): array
    {
        return $this->constraintsProvider();
    }

    public function uppercaseConstraintsProviderTrait(): array
    {
        return $this->constraintsProvider();
    }
}
