<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Provider;

use ArrayIterator;
use PDO;
use stdClass;
use Yiisoft\Db\Command\Param;
use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Constraint\ForeignKeyConstraint;
use Yiisoft\Db\Expression\ArrayExpression;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Expression\JsonExpression;
use Yiisoft\Db\Constant\PhpType;
use Yiisoft\Db\Expression\StructuredExpression;
use Yiisoft\Db\Schema\Column\ArrayColumn;
use Yiisoft\Db\Schema\Column\ArrayLazyColumn;
use Yiisoft\Db\Schema\Column\BigIntColumn;
use Yiisoft\Db\Schema\Column\BinaryColumn;
use Yiisoft\Db\Schema\Column\BitColumn;
use Yiisoft\Db\Schema\Column\BooleanColumn;
use Yiisoft\Db\Schema\Column\DoubleColumn;
use Yiisoft\Db\Schema\Column\IntegerColumn;
use Yiisoft\Db\Schema\Column\JsonColumn;
use Yiisoft\Db\Schema\Column\JsonLazyColumn;
use Yiisoft\Db\Schema\Column\StringColumn;
use Yiisoft\Db\Schema\Column\StructuredColumn;
use Yiisoft\Db\Schema\Column\StructuredLazyColumn;
use Yiisoft\Db\Schema\Data\LazyArray;
use Yiisoft\Db\Schema\Data\JsonLazyArray;
use Yiisoft\Db\Schema\Data\StructuredLazyArray;

use function fopen;

class ColumnProvider
{
    public static function predefinedTypes(): array
    {
        return [
            // [class, type, phpType]
            'integer' => [IntegerColumn::class, ColumnType::INTEGER, PhpType::INT],
            'bigint' => [BigIntColumn::class, ColumnType::BIGINT, PhpType::STRING],
            'double' => [DoubleColumn::class, ColumnType::DOUBLE, PhpType::FLOAT],
            'string' => [StringColumn::class, ColumnType::STRING, PhpType::STRING],
            'binary' => [BinaryColumn::class, ColumnType::BINARY, PhpType::MIXED],
            'bit' => [BitColumn::class, ColumnType::BIT, PhpType::INT],
            'boolean' => [BooleanColumn::class, ColumnType::BOOLEAN, PhpType::BOOL],
            'array' => [ArrayColumn::class, ColumnType::ARRAY, PhpType::ARRAY],
            'structured' => [StructuredColumn::class, ColumnType::STRUCTURED, PhpType::ARRAY],
            'json' => [JsonColumn::class, ColumnType::JSON, PhpType::MIXED],
        ];
    }

    public static function dbTypecastColumns(): array
    {
        return [
            'integer' => [
                new IntegerColumn(),
                [
                    // [expected, typecast value]
                    [null, null],
                    [null, ''],
                    [1, 1],
                    [1, 1.0],
                    [1, '1'],
                    [1, true],
                    [0, false],
                    [$expression = new Expression('1'), $expression],
                ],
            ],
            'bigint' => [
                new BigIntColumn(),
                [
                    [null, null],
                    [null, ''],
                    [1, 1],
                    [1, 1.0],
                    [1, '1'],
                    [1, true],
                    [0, false],
                    ['12345678901234567890', '12345678901234567890'],
                    [$expression = new Expression('1'), $expression],
                ],
            ],
            'double' => [
                new DoubleColumn(),
                [
                    [null, null],
                    [null, ''],
                    [1.0, 1.0],
                    [1.0, 1],
                    [1.0, '1'],
                    [1.0, true],
                    [0.0, false],
                    [$expression = new Expression('1'), $expression],
                ],
            ],
            'string' => [
                new StringColumn(),
                [
                    [null, null],
                    ['', ''],
                    ['1', 1],
                    ['1', true],
                    ['0', false],
                    ['string', 'string'],
                    [$resource = fopen('php://memory', 'rb'), $resource],
                    [$expression = new Expression('expression'), $expression],
                ],
            ],
            'binary' => [
                new BinaryColumn(),
                [
                    [null, null],
                    ['1', 1],
                    ['1', true],
                    ['0', false],
                    [new Param("\x10\x11\x12", PDO::PARAM_LOB), "\x10\x11\x12"],
                    [$resource = fopen('php://memory', 'rb'), $resource],
                    [$expression = new Expression('expression'), $expression],
                ],
            ],
            'bit' => [
                new BitColumn(),
                [
                    [null, null],
                    [null, ''],
                    [1, 1],
                    [1, 1.0],
                    [1, '1'],
                    [10, 10],
                    [10, '10'],
                    [1, true],
                    [0, false],
                    [$expression = new Expression('expression'), $expression],
                ],
            ],
            'boolean' => [
                new BooleanColumn(),
                [
                    [null, null],
                    [null, ''],
                    [true, true],
                    [true, 1],
                    [true, 1.0],
                    [true, '1'],
                    [false, false],
                    [false, 0],
                    [false, 0.0],
                    [false, '0'],
                    [$expression = new Expression('expression'), $expression],
                ],
            ],
            'json' => [
                new JsonColumn(),
                [
                    [null, null],
                    [new JsonExpression(''), ''],
                    [new JsonExpression(1), 1],
                    [new JsonExpression(true), true],
                    [new JsonExpression(false), false],
                    [new JsonExpression('string'), 'string'],
                    [new JsonExpression([1, 2, 3]), [1, 2, 3]],
                    [new JsonExpression(['key' => 'value']), ['key' => 'value']],
                    [new JsonExpression(['a' => 1]), ['a' => 1]],
                    [new JsonExpression(new stdClass()), new stdClass()],
                    [$expression = new JsonExpression([1, 2, 3]), $expression],
                    [$expression = new Expression('expression'), $expression],
                ],
            ],
            'array' => [
                $arrayCol = new ArrayColumn(),
                [
                    [null, null],
                    [new ArrayExpression([], $arrayCol), []],
                    [new ArrayExpression([1, 2, 3], $arrayCol), [1, 2, 3]],
                    [new ArrayExpression($iterator = new ArrayIterator([1, 2, 3]), $arrayCol), $iterator],
                    [new ArrayExpression('[1,2,3]', $arrayCol), '[1,2,3]'],
                    [$expression = new Expression('expression'), $expression],
                ],
            ],
            'structured' => [
                $structuredCol = new StructuredColumn(),
                [
                    [null, null],
                    [new StructuredExpression([], $structuredCol), []],
                    [new StructuredExpression(['value' => 1, 'currency_code' => 'USD'], $structuredCol), ['value' => 1, 'currency_code' => 'USD']],
                    [new StructuredExpression($iterator = new ArrayIterator(['value' => 1, 'currency_code' => 'USD']), $structuredCol), $iterator],
                    [new StructuredExpression('[1,"USD"]', $structuredCol), '[1,"USD"]'],
                    [$expression = new Expression('expression'), $expression],
                ],
            ],
        ];
    }

    public static function phpTypecastColumns(): array
    {
        return [
            'integer' => [
                new IntegerColumn(),
                [
                    // [expected, typecast value]
                    [null, null],
                    [1, 1],
                    [1, '1'],
                ],
            ],
            'bigint' => [
                new BigIntColumn(),
                [
                    [null, null],
                    ['1', 1],
                    ['1', '1'],
                    ['12345678901234567890', '12345678901234567890'],
                ],
            ],
            'double' => [
                new DoubleColumn(),
                [
                    [null, null],
                    [1.0, 1.0],
                    [1.0, '1.0'],
                ],
            ],
            'string' => [
                new StringColumn(),
                [
                    [null, null],
                    ['', ''],
                    ['string', 'string'],
                ],
            ],
            'binary' => [
                new BinaryColumn(),
                [
                    [null, null],
                    ['', ''],
                    ["\x10\x11\x12", "\x10\x11\x12"],
                    [$resource = fopen('php://memory', 'rb'), $resource],
                ],
            ],
            'bit' => [
                new BitColumn(),
                [
                    [null, null],
                    [1, 1],
                    [1, '1'],
                    [10, 10],
                    [10, '10'],
                ],
            ],
            'boolean' => [
                new BooleanColumn(),
                [
                    [null, null],
                    [true, true],
                    [true, '1'],
                    [false, false],
                    [false, '0'],
                    [false, "\0"],
                ],
            ],
            'json' => [
                new JsonColumn(),
                [
                    [null, null],
                    [null, 'null'],
                    ['', '""'],
                    [1.0, '1.0'],
                    [1, '1'],
                    [true, 'true'],
                    [false, 'false'],
                    ['string', '"string"'],
                    [[1, 2, 3], '[1,2,3]'],
                    [['key' => 'value'], '{"key":"value"}'],
                    [['a' => 1], '{"a":1}'],
                ],
            ],
            'jsonLazy' => [
                new JsonLazyColumn(),
                [
                    [null, null],
                    [null, 'null'],
                    ['', '""'],
                    [1.0, '1.0'],
                    [1, '1'],
                    [true, 'true'],
                    [false, 'false'],
                    ['string', '"string"'],
                    [new JsonLazyArray('[1,2,3]'), '[1,2,3]'],
                    [new JsonLazyArray('{"key":"value"}'), '{"key":"value"}'],
                ],
            ],
            'array' => [
                new ArrayColumn(),
                [
                    [null, null],
                    [[], '[]'],
                    [[], '{}'],
                    [[1, 2, 3], '[1,2,3]'],
                    [['1', '2', '3'], '["1","2","3"]'],
                    [['key' => 'value'], '{"key":"value"}'],
                ],
            ],
            'arrayLazy' => [
                new ArrayLazyColumn(),
                [
                    [null, null],
                    [new LazyArray('[]'), '[]'],
                    [new LazyArray('{}'), '{}'],
                    [new LazyArray('[1,2,3]'), '[1,2,3]'],
                ],
            ],
            'structured' => [
                new StructuredColumn(),
                [
                    [null, null],
                    [[], '[]'],
                    [[], '{}'],
                    [[1, true], '[1,true]'],
                    [['key' => 'value'], '{"key":"value"}'],
                ],
            ],
            'structuredLazy' => [
                $structuredCol = (new StructuredLazyColumn())->columns(['key' => new StringColumn()]),
                [
                    [null, null],
                    [new StructuredLazyArray('[]', $structuredCol->getColumns()), '[]'],
                    [new StructuredLazyArray('{}', $structuredCol->getColumns()), '{}'],
                    [new StructuredLazyArray('[1,true]', $structuredCol->getColumns()), '[1,true]'],
                    [new StructuredLazyArray('{"key":"value"}', $structuredCol->getColumns()), '{"key":"value"}'],
                ],
            ],
        ];
    }

    public static function dbTypecastArrayColumns()
    {
        return [
            // [column, values]
            ColumnType::BOOLEAN => [
                new BooleanColumn(),
                [
                    // [dimension, expected, typecast value]
                    [1, [true, true, true, false, false, false, null], [true, 1, '1', false, 0, '0', null]],
                    [2, [[true, true, true, false, false, false, null]], [[true, 1, '1', false, 0, '0', null]]],
                ],
            ],
            ColumnType::BIT => [
                new BitColumn(),
                [
                    [1, [0b1011, 1001, null], [0b1011, '1001', null]],
                    [2, [[0b1011, 1001, null]], [[0b1011, '1001', null]]],
                ],
            ],
            ColumnType::INTEGER => [
                new IntegerColumn(),
                [
                    [1, [1, 2, 3, null], [1, 2.0, '3', null]],
                    [2, [[1, 2], [3], null], [[1, 2.0], ['3'], null]],
                    [2, [null, null], [null, null]],
                ],
            ],
            ColumnType::BIGINT => [
                new BigIntColumn(),
                [
                    [1, ['1', '2', '3', '9223372036854775807'], [1, 2.0, '3', '9223372036854775807']],
                    [2, [['1', '2'], ['3'], ['9223372036854775807']], [[1, 2.0], ['3'], ['9223372036854775807']]],
                ],
            ],
            ColumnType::DOUBLE => [
                new DoubleColumn(),
                [
                    [1, [1.0, 2.2, 3.3, null], [1, 2.2, '3.3', null]],
                    [2, [[1.0, 2.2], [3.3, null]], [[1, 2.2], ['3.3', null]]],
                ],
            ],
            ColumnType::STRING => [
                new StringColumn(),
                [
                    [1, ['1', '2', '1', '0', '', null], [1, '2', true, false, '', null]],
                    [2, [['1', '2', '1', '0'], [''], [null]], [[1, '2', true, false], [''], [null]]],
                ],
            ],
            ColumnType::BINARY => [
                new BinaryColumn(),
                [
                    [1, [
                        '1',
                        new Param("\x10", PDO::PARAM_LOB),
                        $resource = fopen('php://memory', 'rb'),
                        null,
                    ], [1, "\x10", $resource, null]],
                    [2, [[
                        '1',
                        new Param("\x10", PDO::PARAM_LOB),
                        $resource = fopen('php://memory', 'rb'),
                        null,
                    ]], [[1, "\x10", $resource, null]]],
                ],
            ],
            ColumnType::JSON => [
                new JsonColumn(),
                [
                    [1, [
                        new JsonExpression([1, 2, 3]),
                        new JsonExpression(['key' => 'value']),
                        new JsonExpression(['key' => 'value']),
                        null,
                    ], [[1, 2, 3], ['key' => 'value'], new JsonExpression(['key' => 'value']), null]],
                    [2, [
                        [
                            new JsonExpression([1, 2, 3]),
                            new JsonExpression(['key' => 'value']),
                            new JsonExpression(['key' => 'value']),
                            null,
                        ],
                        null,
                    ], [[[1, 2, 3], ['key' => 'value'], new JsonExpression(['key' => 'value']), null], null]],
                ],
            ],
            ColumnType::STRUCTURED => [
                (new StructuredColumn())->dbType('structured_type'),
                [
                    [
                        1,
                        [
                            new StructuredExpression(['value' => 10, 'currency' => 'USD'], 'structured_type'),
                            null,
                        ],
                        [
                            ['value' => 10, 'currency' => 'USD'],
                            null,
                        ],
                    ],
                    [
                        2,
                        [[
                            new StructuredExpression(['value' => 10, 'currency' => 'USD'], 'structured_type'),
                            null,
                        ]],
                        [[
                            ['value' => 10, 'currency' => 'USD'],
                            null,
                        ]],
                    ],
                ],
            ],
        ];
    }

    public static function construct(): array
    {
        return [
            // parameter, value, method to get value, expected value
            ['autoIncrement', true, 'isAutoIncrement', true],
            ['autoIncrement', false, 'isAutoIncrement', false],
            ['check', 'age > 0', 'getCheck', 'age > 0'],
            ['check', null, 'getCheck', null],
            ['comment', 'Lorem ipsum', 'getComment', 'Lorem ipsum'],
            ['comment', null, 'getComment', null],
            ['computed', true, 'isComputed', true],
            ['computed', false, 'isComputed', false],
            ['dbType', 'integer', 'getDbType', 'integer'],
            ['dbType', null, 'getDbType', null],
            ['defaultValue', 'default_value', 'getDefaultValue', 'default_value'],
            ['defaultValue', null, 'getDefaultValue', null],
            ['enumValues', ['value1', 'value2'], 'getEnumValues', ['value1', 'value2']],
            ['enumValues', null, 'getEnumValues', null],
            ['extra', 'CHARACTER SET utf8mb4', 'getExtra', 'CHARACTER SET utf8mb4'],
            ['extra', null, 'getExtra', null],
            ['name', 'name', 'getName', 'name'],
            ['name', null, 'getName', null],
            ['notNull', true, 'isNotNull', true],
            ['notNull', false, 'isNotNull', false],
            ['primaryKey', true, 'isPrimaryKey', true],
            ['primaryKey', false, 'isPrimaryKey', false],
            ['reference', $fk = new ForeignKeyConstraint(), 'getReference', $fk],
            ['reference', null, 'getReference', null],
            ['scale', 2, 'getScale', 2],
            ['scale', null, 'getScale', null],
            ['size', 255, 'getSize', 255],
            ['size', null, 'getSize', null],
            ['unique', true, 'isUnique', true],
            ['unique', false, 'isUnique', false],
            ['unsigned', true, 'isUnsigned', true],
            ['unsigned', false, 'isUnsigned', false],
        ];
    }
}
