<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Provider;

use PDO;
use stdClass;
use Yiisoft\Db\Command\Param;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Expression\JsonExpression;
use Yiisoft\Db\Constant\PhpType;
use Yiisoft\Db\Schema\Column\BigIntColumnSchema;
use Yiisoft\Db\Schema\Column\BinaryColumnSchema;
use Yiisoft\Db\Schema\Column\BitColumnSchema;
use Yiisoft\Db\Schema\Column\BooleanColumnSchema;
use Yiisoft\Db\Schema\Column\DoubleColumnSchema;
use Yiisoft\Db\Schema\Column\IntegerColumnSchema;
use Yiisoft\Db\Schema\Column\JsonColumnSchema;
use Yiisoft\Db\Schema\Column\StringColumnSchema;
use Yiisoft\Db\Schema\SchemaInterface;

use function fopen;

class ColumnSchemaProvider
{
    public static function predefinedTypes(): array
    {
        return [
            // [class, type, phpType]
            'integer' => [IntegerColumnSchema::class, SchemaInterface::TYPE_INTEGER, PhpType::INT],
            'bigint' => [BigIntColumnSchema::class, SchemaInterface::TYPE_BIGINT, PhpType::STRING],
            'double' => [DoubleColumnSchema::class, SchemaInterface::TYPE_DOUBLE, PhpType::FLOAT],
            'string' => [StringColumnSchema::class, SchemaInterface::TYPE_STRING, PhpType::STRING],
            'binary' => [BinaryColumnSchema::class, SchemaInterface::TYPE_BINARY, PhpType::MIXED],
            'bit' => [BitColumnSchema::class, SchemaInterface::TYPE_BIT, PhpType::INT],
            'boolean' => [BooleanColumnSchema::class, SchemaInterface::TYPE_BOOLEAN, PhpType::BOOL],
            'json' => [JsonColumnSchema::class, SchemaInterface::TYPE_JSON, PhpType::MIXED],
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
}
