<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Provider;

use ArrayIterator;
use Yiisoft\Db\Schema\Data\LazyArrayJson;

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

    public static function indexWithIndexByClosure(): array
    {
        return [
            [
                static fn($row) => $row['key'],
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

    public static function indexWithIncorrectIndexBy(): array
    {
        return [
            'not existed key' => [
                'incorrectKey',
                [
                    ['table.key' => 'value1'],
                    ['table.key' => 'value2'],
                ],
                [
                    ['table.key' => 'value1'],
                    ['table.key' => 'value2'],
                ],
            ],
        ];
    }

    public static function indexWithIndexBy(): array
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

    public static function toArray(): array
    {
        return [
            [[], []],
            [['key' => 'value'], ['key' => 'value']],
            [(object) [], []],
            [(object) ['key' => 'value'], ['key' => 'value']],
            [new ArrayIterator([]), []],
            [new ArrayIterator(['key' => 'value']), ['key' => 'value']],
            [new LazyArrayJson('[]'), []],
            [new LazyArrayJson('[1,2,3]'), [1, 2, 3]],
            [new LazyArrayJson('{"key":"value"}'), ['key' => 'value']],
        ];
    }
}
