<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Provider;

use PDO;
use stdClass;
use Yiisoft\Db\Command\Param;
use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Constraint\ForeignKeyConstraint;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Expression\JsonExpression;
use Yiisoft\Db\Constant\PhpType;
use Yiisoft\Db\Expression\StructuredExpression;
use Yiisoft\Db\Schema\Column\ArrayColumnSchema;
use Yiisoft\Db\Schema\Column\BigIntColumnSchema;
use Yiisoft\Db\Schema\Column\BinaryColumnSchema;
use Yiisoft\Db\Schema\Column\BitColumnSchema;
use Yiisoft\Db\Schema\Column\BooleanColumnSchema;
use Yiisoft\Db\Schema\Column\ColumnBuilder;
use Yiisoft\Db\Schema\Column\DoubleColumnSchema;
use Yiisoft\Db\Schema\Column\IntegerColumnSchema;
use Yiisoft\Db\Schema\Column\JsonColumnSchema;
use Yiisoft\Db\Schema\Column\StringColumnSchema;
use Yiisoft\Db\Schema\Column\StructuredColumnSchema;

use function fopen;

class ColumnSchemaProvider
{
    public static function predefinedTypes(): array
    {
        return [
            // [class, type, phpType]
            'integer' => [IntegerColumnSchema::class, ColumnType::INTEGER, PhpType::INT],
            'bigint' => [BigIntColumnSchema::class, ColumnType::BIGINT, PhpType::STRING],
            'double' => [DoubleColumnSchema::class, ColumnType::DOUBLE, PhpType::FLOAT],
            'string' => [StringColumnSchema::class, ColumnType::STRING, PhpType::STRING],
            'binary' => [BinaryColumnSchema::class, ColumnType::BINARY, PhpType::MIXED],
            'bit' => [BitColumnSchema::class, ColumnType::BIT, PhpType::INT],
            'boolean' => [BooleanColumnSchema::class, ColumnType::BOOLEAN, PhpType::BOOL],
            'array' => [ArrayColumnSchema::class, ColumnType::ARRAY, PhpType::ARRAY],
            'structured' => [StructuredColumnSchema::class, ColumnType::STRUCTURED, PhpType::ARRAY],
            'json' => [JsonColumnSchema::class, ColumnType::JSON, PhpType::MIXED],
        ];
    }

    public static function dbTypecastColumns(): array
    {
        return [
            'integer' => [
                IntegerColumnSchema::class,
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
                BigIntColumnSchema::class,
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
                DoubleColumnSchema::class,
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
                StringColumnSchema::class,
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
                BinaryColumnSchema::class,
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
                BitColumnSchema::class,
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
                BooleanColumnSchema::class,
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
                JsonColumnSchema::class,
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
                ],
            ],
        ];
    }

    public static function phpTypecastColumns(): array
    {
        return [
            'integer' => [
                IntegerColumnSchema::class,
                [
                    // [expected, typecast value]
                    [null, null],
                    [1, 1],
                    [1, '1'],
                ],
            ],
            'bigint' => [
                BigIntColumnSchema::class,
                [
                    [null, null],
                    ['1', 1],
                    ['1', '1'],
                    ['12345678901234567890', '12345678901234567890'],
                ],
            ],
            'double' => [
                DoubleColumnSchema::class,
                [
                    [null, null],
                    [1.0, 1.0],
                    [1.0, '1.0'],
                ],
            ],
            'string' => [
                StringColumnSchema::class,
                [
                    [null, null],
                    ['', ''],
                    ['string', 'string'],
                ],
            ],
            'binary' => [
                BinaryColumnSchema::class,
                [
                    [null, null],
                    ['', ''],
                    ["\x10\x11\x12", "\x10\x11\x12"],
                    [$resource = fopen('php://memory', 'rb'), $resource],
                ],
            ],
            'bit' => [
                BitColumnSchema::class,
                [
                    [null, null],
                    [1, 1],
                    [1, '1'],
                    [10, 10],
                    [10, '10'],
                ],
            ],
            'boolean' => [
                BooleanColumnSchema::class,
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
                JsonColumnSchema::class,
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
        ];
    }

    public static function dbTypecastArrayColumns()
    {
        return [
            // [column, values]
            ColumnType::BOOLEAN => [
                ColumnBuilder::boolean(),
                [
                    // [dimension, expected, typecast value]
                    [1, [true, true, true, false, false, false, null], [true, 1, '1', false, 0, '0', null]],
                    [2, [[true, true, true, false, false, false, null]], [[true, 1, '1', false, 0, '0', null]]],
                ],
            ],
            ColumnType::BIT => [
                ColumnBuilder::bit(),
                [
                    [1, [0b1011, 1001, null], [0b1011, '1001', null]],
                    [2, [[0b1011, 1001, null]], [[0b1011, '1001', null]]],
                ],
            ],
            ColumnType::INTEGER => [
                ColumnBuilder::integer(),
                [
                    [1, [1, 2, 3, null], [1, 2.0, '3', null]],
                    [2, [[1, 2], [3], null], [[1, 2.0], ['3'], null]],
                    [2, [null, null], [null, null]],
                ],
            ],
            ColumnType::BIGINT => [
                new BigIntColumnSchema(),
                [
                    [1, ['1', '2', '3', '9223372036854775807'], [1, 2.0, '3', '9223372036854775807']],
                    [2, [['1', '2'], ['3'], ['9223372036854775807']], [[1, 2.0], ['3'], ['9223372036854775807']]],
                ],
            ],
            ColumnType::DOUBLE => [
                ColumnBuilder::double(),
                [
                    [1, [1.0, 2.2, 3.3, null], [1, 2.2, '3.3', null]],
                    [2, [[1.0, 2.2], [3.3, null]], [[1, 2.2], ['3.3', null]]],
                ],
            ],
            ColumnType::STRING => [
                ColumnBuilder::string(),
                [
                    [1, ['1', '2', '1', '0', '', null], [1, '2', true, false, '', null]],
                    [2, [['1', '2', '1', '0'], [''], [null]], [[1, '2', true, false], [''], [null]]],
                ],
            ],
            ColumnType::BINARY => [
                ColumnBuilder::binary(),
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
                ColumnBuilder::json(),
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
                ColumnBuilder::structured('structured_type'),
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
