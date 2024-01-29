<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Provider;

use PDO;
use stdClass;
use Yiisoft\Db\Command\Param;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Expression\JsonExpression;
use Yiisoft\Db\Schema\Column\BigIntColumn;
use Yiisoft\Db\Schema\Column\BinaryColumn;
use Yiisoft\Db\Schema\Column\BooleanColumn;
use Yiisoft\Db\Schema\Column\DoubleColumn;
use Yiisoft\Db\Schema\Column\IntegerColumn;
use Yiisoft\Db\Schema\Column\JsonColumn;
use Yiisoft\Db\Schema\Column\StringColumn;
use Yiisoft\Db\Schema\SchemaInterface;

use function fopen;

class ColumnSchemaProvider
{
    public static function predefinedTypes(): array
    {
        return [
            // [class, type, phpType]
            'integer' => [IntegerColumn::class, SchemaInterface::TYPE_INTEGER, SchemaInterface::PHP_TYPE_INTEGER],
            'bigint' => [BigIntColumn::class, SchemaInterface::TYPE_BIGINT, SchemaInterface::PHP_TYPE_INTEGER],
            'double' => [DoubleColumn::class, SchemaInterface::TYPE_DOUBLE, SchemaInterface::PHP_TYPE_DOUBLE],
            'string' => [StringColumn::class, SchemaInterface::TYPE_STRING, SchemaInterface::PHP_TYPE_STRING],
            'binary' => [BinaryColumn::class, SchemaInterface::TYPE_BINARY, SchemaInterface::PHP_TYPE_RESOURCE],
            'boolean' => [BooleanColumn::class, SchemaInterface::TYPE_BOOLEAN, SchemaInterface::PHP_TYPE_BOOLEAN],
            'json' => [JsonColumn::class, SchemaInterface::TYPE_JSON, SchemaInterface::PHP_TYPE_ARRAY],
        ];
    }

    public static function dbTypecastColumns(): array
    {
        return [
            'integer' => [
                IntegerColumn::class,
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
                BigIntColumn::class,
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
                DoubleColumn::class,
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
                StringColumn::class,
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
                BinaryColumn::class,
                [
                    [null, null],
                    ['', ''],
                    ['1', 1],
                    ['1', true],
                    ['0', false],
                    [new Param("\x10\x11\x12", PDO::PARAM_LOB), "\x10\x11\x12"],
                    [$resource = fopen('php://memory', 'rb'), $resource],
                    [$expression = new Expression('expression'), $expression],
                ],
            ],
            'boolean' => [
                BooleanColumn::class,
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
                    [false, "\0"],
                    [$expression = new Expression('expression'), $expression],
                ],
            ],
            'json' => [
                JsonColumn::class,
                [
                    [null, null],
                    [new JsonExpression('', 'json'), ''],
                    [new JsonExpression(1, 'json'), 1],
                    [new JsonExpression(true, 'json'), true],
                    [new JsonExpression(false, 'json'), false],
                    [new JsonExpression('string', 'json'), 'string'],
                    [new JsonExpression([1, 2, 3], 'json'), [1, 2, 3]],
                    [new JsonExpression(['key' => 'value'], 'json'), ['key' => 'value']],
                    [new JsonExpression(new stdClass(), 'json'), new stdClass()],
                    [$expression = new JsonExpression([1, 2, 3]), $expression],
                ],
            ],
        ];
    }

    public static function phpTypecastColumns(): array
    {
        return [
            'integer' => [
                IntegerColumn::class,
                [
                    // [expected, typecast value]
                    [null, null],
                    [1, 1],
                    [1, '1'],
                ],
            ],
            'bigint' => [
                BigIntColumn::class,
                [
                    [null, null],
                    [1, 1],
                    [1, '1'],
                    ['12345678901234567890', '12345678901234567890'],
                ],
            ],
            'double' => [
                DoubleColumn::class,
                [
                    [null, null],
                    [1.0, 1.0],
                    [1.0, '1.0'],
                ],
            ],
            'string' => [
                StringColumn::class,
                [
                    [null, null],
                    ['', ''],
                    ['string', 'string'],
                    [$resource = fopen('php://memory', 'rb'), $resource],
                ],
            ],
            'binary' => [
                BinaryColumn::class,
                [
                    [null, null],
                    ['', ''],
                    ["\x10\x11\x12", "\x10\x11\x12"],
                    [$resource = fopen('php://memory', 'rb'), $resource],
                ],
            ],
            'boolean' => [
                BooleanColumn::class,
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
                JsonColumn::class,
                [
                    [null, null],
                    ['', '""'],
                    [1.0, '1.0'],
                    [1, '1'],
                    [true, 'true'],
                    [false, 'false'],
                    ['string', '"string"'],
                    [[1, 2, 3], '[1,2,3]'],
                    [['key' => 'value'], '{"key":"value"}'],
                ],
            ],
        ];
    }
}
