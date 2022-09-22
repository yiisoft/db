<?php

declare(strict_types=1);

namespace Yiisoft\Db\TestSupport;

use PDO;
use Yiisoft\Db\Constraint\CheckConstraint;
use Yiisoft\Db\Constraint\Constraint;
use Yiisoft\Db\Constraint\DefaultValueConstraint;
use Yiisoft\Db\Constraint\ForeignKeyConstraint;
use Yiisoft\Db\Constraint\IndexConstraint;
use Yiisoft\Db\Schema\ColumnSchema;
use Yiisoft\Db\Schema\Schema;

use Yiisoft\Db\Schema\TableSchemaInterface;
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

        $db->getActivePDO()->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);

        $this->assertCount(count($db->getSchema()->getTableNames()), $db->getSchema()->getTableSchemas());

        $db->getActivePDO()->setAttribute(PDO::ATTR_CASE, PDO::CASE_UPPER);

        $this->assertCount(count($db->getSchema()->getTableNames()), $db->getSchema()->getTableSchemas());
    }

    public function testGetSchemaPrimaryKeys(): void
    {
        $db = $this->getConnection(false);

        $tablePks = $db->getSchema()->getSchemaPrimaryKeys();

        /** @psalm-suppress RedundantCondition */
        $this->assertIsArray($tablePks);
        $this->assertContainsOnlyInstancesOf(Constraint::class, $tablePks);
    }

    public function testGetSchemaChecks(): void
    {
        $db = $this->getConnection(false);

        $tableChecks = $db->getSchema()->getSchemaChecks();

        /** @psalm-suppress RedundantCondition */
        $this->assertIsArray($tableChecks);

        foreach ($tableChecks as $checks) {
            $this->assertIsArray($checks);
            $this->assertContainsOnlyInstancesOf(CheckConstraint::class, $checks);
        }
    }

    public function testGetSchemaDefaultValues(): void
    {
        $db = $this->getConnection(false);

        $tableDefaultValues = $db->getSchema()->getSchemaDefaultValues();

        /** @psalm-suppress RedundantCondition */
        $this->assertIsArray($tableDefaultValues);

        foreach ($tableDefaultValues as $defaultValues) {
            $this->assertIsArray($defaultValues);
            $this->assertContainsOnlyInstancesOf(DefaultValueConstraint::class, $defaultValues);
        }
    }

    public function testGetSchemaForeignKeys(): void
    {
        $db = $this->getConnection(false);

        $tableForeignKeys = $db->getSchema()->getSchemaForeignKeys();

        /** @psalm-suppress RedundantCondition */
        $this->assertIsArray($tableForeignKeys);

        foreach ($tableForeignKeys as $foreignKeys) {
            $this->assertIsArray($foreignKeys);
            $this->assertContainsOnlyInstancesOf(ForeignKeyConstraint::class, $foreignKeys);
        }
    }

    public function testGetSchemaIndexes(): void
    {
        $db = $this->getConnection(false);

        $tableIndexes = $db->getSchema()->getSchemaIndexes();

        /** @psalm-suppress RedundantCondition */
        $this->assertIsArray($tableIndexes);

        foreach ($tableIndexes as $indexes) {
            $this->assertIsArray($indexes);
            $this->assertContainsOnlyInstancesOf(IndexConstraint::class, $indexes);
        }
    }

    public function testGetSchemaUniques(): void
    {
        $db = $this->getConnection(false);

        $tableUniques = $db->getSchema()->getSchemaUniques();

        /** @psalm-suppress RedundantCondition */
        $this->assertIsArray($tableUniques);

        foreach ($tableUniques as $uniques) {
            $this->assertIsArray($uniques);
            $this->assertContainsOnlyInstancesOf(Constraint::class, $uniques);
        }
    }

    public function testGetNonExistingTableSchema(): void
    {
        $this->assertNull($this->getConnection()->getSchema()->getTableSchema('nonexisting_table'));
    }

    public function testSchemaCache(): void
    {
        $db = $this->getConnection();

        $schema = $db->getSchema();

        $this->assertNotNull($this->schemaCache);
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

        $this->assertNotNull($this->schemaCache);

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

        $this->assertNotNull($table);

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
        $this->assertNotNull($table);

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
                /** @psalm-suppress UndefinedMethod */
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

        $this->assertNotNull($table);
        $this->assertEquals(-123, $table->getColumn('tinyint_col')?->getDefaultValue());
        $this->assertEquals(-123, $table->getColumn('smallint_col')?->getDefaultValue());
        $this->assertEquals(-123, $table->getColumn('int_col')?->getDefaultValue());
        $this->assertEquals(-123, $table->getColumn('bigint_col')?->getDefaultValue());
        $this->assertEquals(-12345.6789, $table->getColumn('float_col')?->getDefaultValue());
        $this->assertEquals(-33.22, $table->getColumn('numeric_col')?->getDefaultValue());
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
            $this->assertInstanceOf(TableSchemaInterface::class, $tableSchema, $tableName);
        }
    }

    public function testGetColumnNoExist(): void
    {
        $schema = $this->getConnection()->getSchema();
        $table = $schema->getTableSchema('negative_default_values');

        $this->assertNotNull($table);
        $this->assertNull($table->getColumn('no_exist'));
    }

    public function testQuoterEscapingValue()
    {
        $db = $this->getConnection(true);
        $quoter = $db->getQuoter();

        $db->createCommand('delete from {{quoter}}')->execute();
        $data = $this->generateQuoterEscapingValues();

        foreach ($data as $index => $value) {
            $quotedName = $quoter->quoteValue('testValue_' . $index);
            $quoteValue = $quoter->quoteValue($value);

            $db->createCommand('insert into {{quoter}}([[name]], [[description]]) values(' . $quotedName . ', ' . $quoteValue . ')')->execute();
            $result = $db->createCommand('select * from {{quoter}} where [[name]]=' . $quotedName)->queryOne();
            $this->assertEquals($value, $result['description']);
        }
    }

    public function generateQuoterEscapingValues()
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

    public function testQuoterEscapingValueFull()
    {
        $this->markTestSkipped('Very long test - only for check quoteValue');
        $template = 'aaaaa{1}aaa{1}aaaabbbbb{2}bbbb{2}bbbb';

        $db = $this->getConnection(true);
        $quoter = $db->getQuoter();

        $db->createCommand('delete from {{quoter}}')->execute();

        for ($symbol1 = 1; $symbol1 <= 127; $symbol1++) {
            for ($symbol2 = 1; $symbol2 <= 127; $symbol2++) {
                $quotedName = $quoter->quoteValue('test_' . $symbol1 . '_' . $symbol2);
                $testString = str_replace(['{1}', '{2}',], [chr($symbol1), chr($symbol2)], $template);

                $quoteValue = $quoter->quoteValue($testString);

                $db->createCommand('insert into {{quoter}}([[name]], [[description]]) values(' . $quotedName . ', ' . $quoteValue . ')')->execute();
                $result = $db->createCommand('select * from {{quoter}} where [[name]]=' . $quotedName)->queryOne();
                $this->assertEquals($testString, $result['description']);
            }
        }
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
            /** @psalm-suppress PossiblyInvalidArgument */
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
                $actualConstraintName = $actualConstraint->getName();
                $this->assertIsString($actualConstraintName);
                $actualConstraint->name(new AnyCaseValue($actualConstraintName));
            }
        }
    }

    public function constraintsProviderTrait(): array
    {
        return [
            '1: primary key' => [
                'T_constraints_1',
                Schema::PRIMARY_KEY,
                (new Constraint())
                    ->name(AnyValue::getInstance())
                    ->columnNames(['C_id']),
            ],
            '1: check' => [
                'T_constraints_1',
                Schema::CHECKS,
                [
                    (new CheckConstraint())
                        ->name(AnyValue::getInstance())
                        ->columnNames(['C_check'])
                        ->expression("C_check <> ''"),
                ],
            ],
            '1: unique' => [
                'T_constraints_1',
                Schema::UNIQUES,
                [
                    (new Constraint())
                        ->name('CN_unique')
                        ->columnNames(['C_unique']),
                ],
            ],
            '1: index' => [
                'T_constraints_1',
                Schema::INDEXES,
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
            '1: default' => ['T_constraints_1', Schema::DEFAULT_VALUES, false],

            '2: primary key' => [
                'T_constraints_2',
                Schema::PRIMARY_KEY,
                (new Constraint())
                    ->name('CN_pk')
                    ->columnNames(['C_id_1', 'C_id_2']),
            ],
            '2: unique' => [
                'T_constraints_2',
                Schema::UNIQUES,
                [
                    (new Constraint())
                        ->name('CN_constraints_2_multi')
                        ->columnNames(['C_index_2_1', 'C_index_2_2']),
                ],
            ],
            '2: index' => [
                'T_constraints_2',
                Schema::INDEXES,
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
            '2: check' => ['T_constraints_2', Schema::CHECKS, []],
            '2: default' => ['T_constraints_2', Schema::DEFAULT_VALUES, false],

            '3: primary key' => ['T_constraints_3', Schema::PRIMARY_KEY, null],
            '3: foreign key' => [
                'T_constraints_3',
                Schema::FOREIGN_KEYS,
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
            '3: unique' => ['T_constraints_3', Schema::UNIQUES, []],
            '3: index' => [
                'T_constraints_3',
                Schema::INDEXES,
                [
                    (new IndexConstraint())
                        ->name('CN_constraints_3')
                        ->columnNames(['C_fk_id_1', 'C_fk_id_2'])
                        ->unique(false)
                        ->primary(false),
                ],
            ],
            '3: check' => ['T_constraints_3', Schema::CHECKS, []],
            '3: default' => ['T_constraints_3', Schema::DEFAULT_VALUES, false],

            '4: primary key' => [
                'T_constraints_4',
                Schema::PRIMARY_KEY,
                (new Constraint())
                    ->name(AnyValue::getInstance())
                    ->columnNames(['C_id']),
            ],
            '4: unique' => [
                'T_constraints_4',
                Schema::UNIQUES,
                [
                    (new Constraint())
                        ->name('CN_constraints_4')
                        ->columnNames(['C_col_1', 'C_col_2']),
                ],
            ],
            '4: check' => ['T_constraints_4', Schema::CHECKS, []],
            '4: default' => ['T_constraints_4', Schema::DEFAULT_VALUES, false],
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
