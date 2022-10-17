<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Provider;

use Yiisoft\Db\Constraint\CheckConstraint;
use Yiisoft\Db\Constraint\Constraint;
use Yiisoft\Db\Constraint\DefaultValueConstraint;
use Yiisoft\Db\Constraint\ForeignKeyConstraint;
use Yiisoft\Db\Constraint\IndexConstraint;
use Yiisoft\Db\Schema\Schema;
use Yiisoft\Db\Tests\Support\AnyValue;

abstract class AbstractConstraintProvider
{
    protected function getTableConstraints(): array
    {
        return [
            '1: primary key' => [
                'T_constraints_1',
                Schema::PRIMARY_KEY,
                (new Constraint())->name(AnyValue::getInstance())->columnNames(['C_id']),
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
                    (new Constraint())->name('CN_unique')->columnNames(['C_unique']),
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
                (new Constraint())->name('CN_pk')->columnNames(['C_id_1', 'C_id_2']),
            ],
            '2: unique' => [
                'T_constraints_2',
                Schema::UNIQUES,
                [
                    (new Constraint())->name('CN_constraints_2_multi')->columnNames(['C_index_2_1', 'C_index_2_2']),
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
                (new Constraint())->name(AnyValue::getInstance())->columnNames(['C_id']),
            ],
            '4: unique' => [
                'T_constraints_4',
                Schema::UNIQUES,
                [
                    (new Constraint())->name('CN_constraints_4')->columnNames(['C_col_1', 'C_col_2']),
                ],
            ],
            '4: check' => ['T_constraints_4', Schema::CHECKS, []],
            '4: default' => ['T_constraints_4', Schema::DEFAULT_VALUES, false],
        ];
    }
}
