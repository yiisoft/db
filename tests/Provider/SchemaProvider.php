<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Provider;

use PDO;
use Yiisoft\Db\Constraint\CheckConstraint;
use Yiisoft\Db\Constraint\Constraint;
use Yiisoft\Db\Constraint\ForeignKeyConstraint;
use Yiisoft\Db\Constraint\IndexConstraint;
use Yiisoft\Db\Schema\SchemaInterface;
use Yiisoft\Db\Tests\Support\AnyValue;

class SchemaProvider
{
    public static function columns(): array
    {
        return [];
    }

    public static function columnsTypeChar(): array
    {
        return [
            ['char_col', 'char', 100, 'char(100)'],
            ['char_col2', 'string', 100, 'varchar(100)'],
            ['char_col3', 'text', null, 'text'],
        ];
    }

    public static function constraints(): array
    {
        return [
            '1: primary key' => [
                'T_constraints_1',
                SchemaInterface::PRIMARY_KEY,
                (new Constraint())->name(AnyValue::getInstance())->columnNames(['C_id']),
            ],
            '1: check' => [
                'T_constraints_1',
                SchemaInterface::CHECKS,
                [
                    (new CheckConstraint())
                        ->name(AnyValue::getInstance())
                        ->columnNames(['C_check'])
                        ->expression("C_check <> ''"),
                ],
            ],
            '1: unique' => [
                'T_constraints_1',
                SchemaInterface::UNIQUES,
                [(new Constraint())->name('CN_unique')->columnNames(['C_unique'])],
            ],
            '1: index' => [
                'T_constraints_1',
                SchemaInterface::INDEXES,
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
            '1: default' => ['T_constraints_1', SchemaInterface::DEFAULT_VALUES, false],

            '2: primary key' => [
                'T_constraints_2',
                SchemaInterface::PRIMARY_KEY,
                (new Constraint())->name('CN_pk')->columnNames(['C_id_1', 'C_id_2']),
            ],
            '2: unique' => [
                'T_constraints_2',
                SchemaInterface::UNIQUES,
                [(new Constraint())->name('CN_constraints_2_multi')->columnNames(['C_index_2_1', 'C_index_2_2'])],
            ],
            '2: index' => [
                'T_constraints_2',
                SchemaInterface::INDEXES,
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
            '2: check' => ['T_constraints_2', SchemaInterface::CHECKS, []],
            '2: default' => ['T_constraints_2', SchemaInterface::DEFAULT_VALUES, false],

            '3: primary key' => ['T_constraints_3', SchemaInterface::PRIMARY_KEY, null],
            '3: foreign key' => [
                'T_constraints_3',
                SchemaInterface::FOREIGN_KEYS,
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
            '3: unique' => ['T_constraints_3', SchemaInterface::UNIQUES, []],
            '3: index' => [
                'T_constraints_3',
                SchemaInterface::INDEXES,
                [
                    (new IndexConstraint())
                        ->name('CN_constraints_3')
                        ->columnNames(['C_fk_id_1', 'C_fk_id_2'])
                        ->unique(false)
                        ->primary(false),
                ],
            ],
            '3: check' => ['T_constraints_3', SchemaInterface::CHECKS, []],
            '3: default' => ['T_constraints_3', SchemaInterface::DEFAULT_VALUES, false],

            '4: primary key' => [
                'T_constraints_4',
                SchemaInterface::PRIMARY_KEY,
                (new Constraint())->name(AnyValue::getInstance())->columnNames(['C_id']),
            ],
            '4: unique' => [
                'T_constraints_4',
                SchemaInterface::UNIQUES,
                [(new Constraint())->name('CN_constraints_4')->columnNames(['C_col_1', 'C_col_2'])],
            ],
            '4: check' => ['T_constraints_4', SchemaInterface::CHECKS, []],
            '4: default' => ['T_constraints_4', SchemaInterface::DEFAULT_VALUES, false],
        ];
    }

    public static function pdoAttributes(): array
    {
        return [[[PDO::ATTR_EMULATE_PREPARES => true]], [[PDO::ATTR_EMULATE_PREPARES => false]]];
    }

    public static function tableSchema(): array
    {
        return [
            ['negative_default_values', 'negative_default_values'],
            ['profile', 'profile'],
        ];
    }

    public static function tableSchemaCachePrefixes(): array
    {
        $configs = [
            ['prefix' => '', 'name' => 'type'],
            ['prefix' => '', 'name' => '{{%type}}'],
            ['prefix' => 'ty', 'name' => '{{%pe}}'],
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

    public static function withIndexDataProvider(): array
    {
        return [
            [
                'indexType' => SchemaInterface::INDEX_UNIQUE,
                'indexMethod' => null,
                'columnType' => null,
                'isPrimary' => false,
                'isUnique' => true,
            ],
        ];
    }
}
