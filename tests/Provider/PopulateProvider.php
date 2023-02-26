<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Provider;

class PopulateProvider
{
    public static function populate(): array
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

    public static function populateWithIndexByClosure(): array
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

    public static function populateWithIncorrectIndexBy(): array
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

    public static function populateWithIndexBy(): array
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
