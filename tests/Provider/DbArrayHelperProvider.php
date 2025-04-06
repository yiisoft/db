<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Provider;

use ArrayIterator;
use Yiisoft\Db\Schema\Data\JsonLazyArray;

use function is_object;

class DbArrayHelperProvider
{
    public static function index(): array
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

    public static function indexWithIndexBy(): array
    {
        $resultCallback = fn (array $rows) => array_map(
            fn (array|object $row) => ['key' => strtoupper(is_object($row) ? $row->key : $row['key'])],
            $rows,
        );

        return [
            'null key with empty rows' => [
                'expected' => [],
                'rows' => [],
            ],
            'null key' => [
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
                [
                    'value1' => ['key' => 'value1'],
                    'value2' => ['key' => 'value2'],
                ],
                [
                    ['key' => 'value1'],
                    ['key' => 'value2'],
                ],
                'key',
            ],
            'null-key and composite.key' => [
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
                [
                    'value1' => ['table key' => 'value1'],
                    'value2' => ['table key' => 'value2'],
                ],
                [
                    ['table key' => 'value1'],
                    ['table key' => 'value2'],
                ],
                'table key',
            ],
            'composite-key and composite key' => [
                [
                    'value1' => ['table.key' => 'value1'],
                    'value2' => ['table.key' => 'value2'],
                ],
                [
                    ['table.key' => 'value1'],
                    ['table.key' => 'value2'],
                ],
                'table.key',
            ],
            'closure key' => [
                [
                    'value1' => ['key' => 'value1'],
                    'value2' => ['key' => 'value2'],
                ],
                [
                    ['key' => 'value1'],
                    ['key' => 'value2'],
                ],
                static fn ($row) => is_object($row) ? $row->key : $row['key'],
            ],
            'not existed key' => [
                [
                    ['table.key' => 'value1'],
                    ['table.key' => 'value2'],
                ],
                [
                    ['table.key' => 'value1'],
                    ['table.key' => 'value2'],
                ],
                'incorrectKey',
            ],
            'key and resultCallback' => [
                [
                    'value1' => ['key' => 'VALUE1'],
                    'value2' => ['key' => 'VALUE2'],
                ],
                [
                    ['key' => 'value1'],
                    ['key' => 'value2'],
                ],
                'key',
                $resultCallback,
            ],
            'null-key and resultCallback' => [
                [
                    ['key' => 'VALUE1'],
                    ['key' => 'VALUE2'],
                ],
                [
                    ['key' => 'value1'],
                    ['key' => 'value2'],
                ],
                null,
                $resultCallback,
            ],
        ];
    }

    public static function arrange()
    {
        $rows = [
            ['key' => 'value1'],
            ['key' => 'value2'],
        ];
        $resultCallback = fn (array $rows) => array_map(fn (array $row) => ['key' => strtoupper($row['key'])], $rows);

        return [
            [
                'expected' => [],
                'rows' => [],
            ],
            [
                $rows,
                $rows,
            ],
            [
                [
                    'value1' => [['key' => 'value1']],
                    'value2' => [['key' => 'value2']],
                ],
                $rows,
                ['key'],
            ],
            [
                [
                    'value1' => ['key' => 'value1'],
                    'value2' => ['key' => 'value2'],
                ],
                $rows,
                [],
                'key',
            ],
            [
                [
                    'value1' => ['key' => 'value1'],
                    'value2' => ['key' => 'value2'],
                ],
                $rows,
                [],
                static fn ($row) => $row['key'],
            ],
            [
                [
                    ['key' => 'VALUE1'],
                    ['key' => 'VALUE2'],
                ],
                $rows,
                [],
                null,
                $resultCallback,
            ],
            [
                [
                    'value1' => ['value1' => ['key' => 'value1']],
                    'value2' => ['value2' => ['key' => 'value2']],
                ],
                $rows,
                ['key'],
                'key',
            ],
            [
                [
                    'value1' => ['value1' => ['key' => 'value1']],
                    'value2' => ['value2' => ['key' => 'value2']],
                ],
                $rows,
                ['key'],
                static fn ($row) => $row['key'],
            ],
            [
                [
                    'value1' => [['key' => 'VALUE1']],
                    'value2' => [['key' => 'VALUE2']],
                ],
                $rows,
                ['key'],
                null,
                $resultCallback,
            ],
            [
                [
                    'value1' => ['value1' => ['key' => 'VALUE1']],
                    'value2' => ['value2' => ['key' => 'VALUE2']],
                ],
                $rows,
                ['key'],
                'key',
                $resultCallback,
            ],
            [
                [
                    'value1' => ['value1' => ['key' => 'VALUE1']],
                    'value2' => ['value2' => ['key' => 'VALUE2']],
                ],
                $rows,
                ['key'],
                static fn ($row) => $row['key'],
                $resultCallback,
            ],
            [
                [
                    '123' => [
                        'laptop' => [
                            'abc' => ['id' => '123', 'data' => 'abc', 'device' => 'laptop'],
                        ],
                    ],
                    '345' => [
                        'tablet' => [
                            'def' => ['id' => '345', 'data' => 'def', 'device' => 'tablet'],
                        ],
                        'smartphone' => [
                            'hgi' => ['id' => '345', 'data' => 'hgi', 'device' => 'smartphone'],
                        ],
                    ],
                ],
                [
                    ['id' => '123', 'data' => 'abc', 'device' => 'laptop'],
                    ['id' => '345', 'data' => 'def', 'device' => 'tablet'],
                    ['id' => '345', 'data' => 'hgi', 'device' => 'smartphone'],
                ],
                ['id', 'device'],
                'data',
            ],
        ];
    }

    public static function toArray(): array
    {
        return [
            [[], []],
            [['key' => 'value'], ['key' => 'value']],
            [(object) [], []],
            [(object) ['key' => 'value'], ['key' => 'value']],
            [new ArrayIterator([]), []],
            [new ArrayIterator(['key' => 'value']), ['key' => 'value']],
            [new JsonLazyArray('[]'), []],
            [new JsonLazyArray('[1,2,3]'), [1, 2, 3]],
            [new JsonLazyArray('{"key":"value"}'), ['key' => 'value']],
        ];
    }
}
