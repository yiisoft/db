<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Provider;

use PDO;
use Yiisoft\Db\Constant\IndexType;
use Yiisoft\Db\Constant\ReferentialAction;
use Yiisoft\Db\Constraint\CheckConstraint;
use Yiisoft\Db\Constraint\ForeignKeyConstraint;
use Yiisoft\Db\Constraint\IndexConstraint;
use Yiisoft\Db\Schema\SchemaInterface;

class SchemaProvider
{
    public static function columns(): array
    {
        return [];
    }

    public static function resultColumns(): array
    {
        return [];
    }

    public static function constraints(): array
    {
        return [
            '1: primary key' => [
                'T_constraints_1',
                SchemaInterface::PRIMARY_KEY,
                new IndexConstraint('', ['C_id'], true, true),
            ],
            '1: check' => [
                'T_constraints_1',
                SchemaInterface::CHECKS,
                [new CheckConstraint('', ['C_check'], "C_check <> ''")],
            ],
            '1: unique' => [
                'T_constraints_1',
                SchemaInterface::UNIQUES,
                [new IndexConstraint('CN_unique', ['C_unique'], true)],
            ],
            '1: index' => [
                'T_constraints_1',
                SchemaInterface::INDEXES,
                [
                    new IndexConstraint('', ['C_id'], true, true),
                    new IndexConstraint('CN_unique', ['C_unique'], true),
                ],
            ],
            '1: default' => ['T_constraints_1', SchemaInterface::DEFAULT_VALUES, false],

            '2: primary key' => [
                'T_constraints_2',
                SchemaInterface::PRIMARY_KEY,
                new IndexConstraint('CN_pk', ['C_id_1', 'C_id_2'], true, true),
            ],
            '2: unique' => [
                'T_constraints_2',
                SchemaInterface::UNIQUES,
                [new IndexConstraint('CN_constraints_2_multi', ['C_index_2_1', 'C_index_2_2'], true)],
            ],
            '2: index' => [
                'T_constraints_2',
                SchemaInterface::INDEXES,
                [
                    new IndexConstraint('CN_pk', ['C_id_1', 'C_id_2'], true, true),
                    new IndexConstraint('CN_constraints_2_single', ['C_index_1']),
                    new IndexConstraint('CN_constraints_2_multi', ['C_index_2_1', 'C_index_2_2'], true),
                ],
            ],
            '2: check' => ['T_constraints_2', SchemaInterface::CHECKS, []],
            '2: default' => ['T_constraints_2', SchemaInterface::DEFAULT_VALUES, false],

            '3: primary key' => ['T_constraints_3', SchemaInterface::PRIMARY_KEY, null],
            '3: foreign key' => [
                'T_constraints_3',
                SchemaInterface::FOREIGN_KEYS,
                [
                    new ForeignKeyConstraint(
                        'CN_constraints_3',
                        ['C_fk_id_1', 'C_fk_id_2'],
                        'T_constraints_2',
                        ['C_id_1', 'C_id_2'],
                        ReferentialAction::CASCADE,
                        ReferentialAction::CASCADE,
                    ),
                ],
            ],
            '3: unique' => ['T_constraints_3', SchemaInterface::UNIQUES, []],
            '3: index' => [
                'T_constraints_3',
                SchemaInterface::INDEXES,
                [new IndexConstraint('CN_constraints_3', ['C_fk_id_1', 'C_fk_id_2'])],
            ],
            '3: check' => ['T_constraints_3', SchemaInterface::CHECKS, []],
            '3: default' => ['T_constraints_3', SchemaInterface::DEFAULT_VALUES, false],

            '4: primary key' => [
                'T_constraints_4',
                SchemaInterface::PRIMARY_KEY,
                new IndexConstraint('', ['C_id'], true, true),
            ],
            '4: unique' => [
                'T_constraints_4',
                SchemaInterface::UNIQUES,
                [new IndexConstraint('CN_constraints_4', ['C_col_1', 'C_col_2'], true)],
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
                'indexType' => IndexType::UNIQUE,
                'indexMethod' => null,
                'columnType' => null,
                'isPrimary' => false,
                'isUnique' => true,
            ],
        ];
    }
}
