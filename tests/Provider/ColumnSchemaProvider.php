<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Provider;

use PDO;
use stdClass;
use Yiisoft\Db\Command\Param;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Expression\JsonExpression;
use Yiisoft\Db\Schema\Column\BigIntColumnSchema;
use Yiisoft\Db\Schema\Column\BinaryColumnSchema;
use Yiisoft\Db\Schema\Column\BooleanColumnSchema;
use Yiisoft\Db\Schema\Column\DoubleColumnSchema;
use Yiisoft\Db\Schema\Column\IntegerColumnSchema;
use Yiisoft\Db\Schema\Column\JsonColumnSchema;
use Yiisoft\Db\Schema\Column\StringColumnSchema;

use function fopen;

class ColumnSchemaProvider
{
    public static function dbTypecastColumns(): array
    {
        return [[[
            IntegerColumnSchema::class => [
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
            BigIntColumnSchema::class => [
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
            DoubleColumnSchema::class => [
                [null, null],
                [null, ''],
                [1.0, 1.0],
                [1.0, 1],
                [1.0, '1'],
                [1.0, true],
                [0.0, false],
                [$expression = new Expression('1'), $expression],
            ],
            StringColumnSchema::class => [
                [null, null],
                ['', ''],
                ['1', 1],
                ['1', true],
                ['0', false],
                ['string', 'string'],
                [$resource = fopen('php://memory', 'rb'), $resource],
                [$expression = new Expression('expression'), $expression],
            ],
            BinaryColumnSchema::class => [
                [null, null],
                ['1', 1],
                ['1', true],
                ['0', false],
                [new Param("\x10\x11\x12", PDO::PARAM_LOB), "\x10\x11\x12"],
                [$resource = fopen('php://memory', 'rb'), $resource],
                [$expression = new Expression('expression'), $expression],
            ],
            BooleanColumnSchema::class => [
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
                [false, false],
                [$expression = new Expression('expression'), $expression],
            ],
            JsonColumnSchema::class => [
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
        ]]];
    }

    public static function phpTypecastColumns(): array
    {
        return [[[
            IntegerColumnSchema::class => [
                // [expected, typecast value]
                [null, null],
                [1, 1],
                [1, '1'],
            ],
            BigIntColumnSchema::class => [
                [null, null],
                [1, 1],
                [1, '1'],
                ['12345678901234567890', '12345678901234567890'],
            ],
            DoubleColumnSchema::class => [
                [null, null],
                [1.0, 1.0],
                [1.0, '1.0'],
            ],
            StringColumnSchema::class => [
                [null, null],
                ['', ''],
                ['string', 'string'],
                [$resource = fopen('php://memory', 'rb'), $resource],
            ],
            BinaryColumnSchema::class => [
                [null, null],
                ['', ''],
                ["\x10\x11\x12", "\x10\x11\x12"],
                [$resource = fopen('php://memory', 'rb'), $resource],
            ],
            BooleanColumnSchema::class => [
                [null, null],
                [true, true],
                [true, '1'],
                [false, false],
                [false, '0'],
                [false, "\0"],
            ],
            JsonColumnSchema::class => [
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
        ]]];
    }
}
