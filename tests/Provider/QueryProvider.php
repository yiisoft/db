<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Provider;

use Yiisoft\Db\Expression\Expression;

class QueryProvider
{
    public function filterConditionData(): array
    {
        return [
            /* like */
            [['like', 'name', []], null],
            [['not like', 'name', []], null],
            [['or like', 'name', []],  null],
            [['or not like', 'name', []], null],

            /* not */
            [['not', ''], null],

            /* and */
            [['and', '', ''], null],
            [['and', '', 'id=2'], ['and', 'id=2']],
            [['and', 'id=1', ''], ['and', 'id=1']],
            [['and', 'type=1', ['or', '', 'id=2']], ['and', 'type=1', ['or', 'id=2']]],

            /* or */
            [['or', 'id=1', ''], ['or', 'id=1']],
            [['or', 'type=1', ['or', '', 'id=2']], ['or', 'type=1', ['or', 'id=2']]],

            /* between */
            [['between', 'id', 1, null], null],
            [['between', 'id'], null],
            [['between', 'id', 1], null],
            [['not between', 'id', null, 10], null],
            [['between', 'id', 1, 2], ['between', 'id', 1, 2]],

            /* in */
            [['in', 'id', []], null],
            [['not in', 'id', []], null],

            /* simple conditions */
            [['=', 'a', ''], null],
            [['>', 'a', ''], null],
            [['>=', 'a', ''], null],
            [['<', 'a', ''], null],
            [['<=', 'a', ''], null],
            [['<>', 'a', ''], null],
            [['!=', 'a', ''], null],
        ];
    }

    public function normalizeOrderBy(): array
    {
        return [
            ['id', ['id' => 4]],
            [['id'], ['id']],
            ['name ASC, date DESC', ['name' => 4, 'date' => 3]],
            [new Expression('SUBSTR(name, 3, 4) DESC, x ASC'), [new Expression('SUBSTR(name, 3, 4) DESC, x ASC')]],
        ];
    }

    public function normalizeSelect(): array
    {
        return [
            ['exists', ['exists' => 'exists']],
            ['count(*) > 1', ['count(*) > 1']],
            ['name, name, name as X, name as X', ['name' => 'name', 'X' => 'name']],
            [
                ['email', 'address', 'status' => new Expression('1')],
                ['email' => 'email', 'address' => 'address', 'status' => new Expression('1')],
            ],
            [new Expression('1 as Ab'), [new Expression('1 as Ab')]],
        ];
    }

    public function populate(): array
    {
        return [
            [
                [],
            ],
            [
                [['value']],
            ],
            [
                [['key' => 'value']],
            ],
            [
                [['table.key' => 'value']],
            ],
        ];
    }

    public function populateWithIndexByClosure(): array
    {
        return [
            [
                static function ($row) {
                    return $row['key'];
                },
                [
                    ['key' => 'value1'],
                    ['key' => 'value2'],
                ],
                [
                    'value1' => ['key' => 'value1'],
                    'value2' => ['key' => 'value2'],
                ],
            ],
        ];
    }

    public function populateWithIncorrectIndexBy(): array
    {
        return [
            'not existed key' => [
                'incorrectKey',
                [
                    ['table.key' => 'value1'],
                    ['table.key' => 'value2'],
                ],
                [
                    '' => ['table.key' => 'value2'],
                ],
            ],
            'empty key (not found key behavior)' => [
                '',
                [
                    ['table.key' => 'value1'],
                    ['table.key' => 'value2'],
                ],
                [
                    '' => ['table.key' => 'value2'],
                ],
            ],
            'key and composite key (not found key behavior)' => [
                'key',
                [
                    ['table.key' => 'value1'],
                    ['table.key' => 'value2'],
                ],
                [
                    '' => ['table.key' => 'value2'],
                ],
            ],
        ];
    }

    public function populateWithIndexBy(): array
    {
        return [
            'null key with empty rows' => [
                null,
                'rows' => [],
                'expected' => [],
            ],
            'null key' => [
                null,
                [
                    ['key' => 'value1'],
                    ['key' => 'value2'],
                ],
                [
                    ['key' => 'value1'],
                    ['key' => 'value2'],
                ],
            ],
            'correct key' => [
                'key',
                [
                    ['key' => 'value1'],
                    ['key' => 'value2'],
                ],
                [
                    'value1' => ['key' => 'value1'],
                    'value2' => ['key' => 'value2'],
                ],
            ],
            'null-key and composite.key' => [
                null,
                [
                    ['table.key' => 'value1'],
                    ['table.key' => 'value2'],
                ],
                [
                    ['table.key' => 'value1'],
                    ['table.key' => 'value2'],
                ],
            ],
            'key with space' => [
                'table key',
                [
                    ['table key' => 'value1'],
                    ['table key' => 'value2'],
                ],
                [
                    'value1' => ['table key' => 'value1'],
                    'value2' => ['table key' => 'value2'],
                ],
            ],
            'composite-key and simple key' => [
                't.key',
                [
                    [
                        'key' => 'value1',
                        't' => [
                            'key' => 'value2',
                        ],
                    ],
                ],
                [
                    'value2' => [
                        'key' => 'value1',
                        't' => [
                            'key' => 'value2',
                        ],
                    ],
                ],
            ],
            'composite-3-key and simple key' => [
                't1.t2.key',
                [
                    [
                        'key' => 'value1',
                        't1' => [
                            'key' => 'value2',
                            't2' => [
                                'key' => 'value3',
                            ],
                        ],
                    ],
                ],
                [
                    'value3' => [
                        'key' => 'value1',
                        't1' => [
                            'key' => 'value2',
                            't2' => [
                                'key' => 'value3',
                            ],
                        ],
                    ],
                ],
            ],
            'composite-key and composite key' => [
                'table.key',
                [
                    ['table.key' => 'value1'],
                    ['table.key' => 'value2'],
                ],
                [
                    'value1' => ['table.key' => 'value1'],
                    'value2' => ['table.key' => 'value2'],
                ],
            ],
        ];
    }
}
