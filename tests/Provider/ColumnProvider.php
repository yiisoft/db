<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Provider;

use ArrayIterator;
use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use PDO;
use stdClass;
use Yiisoft\Db\Expression\Param;
use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Constraint\ForeignKey;
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
use Yiisoft\Db\Schema\Column\DateTimeColumn;
use Yiisoft\Db\Schema\Column\DoubleColumn;
use Yiisoft\Db\Schema\Column\IntegerColumn;
use Yiisoft\Db\Schema\Column\JsonColumn;
use Yiisoft\Db\Schema\Column\JsonLazyColumn;
use Yiisoft\Db\Schema\Column\StringColumn;
use Yiisoft\Db\Schema\Column\StructuredColumn;
use Yiisoft\Db\Schema\Column\StructuredLazyColumn;
use Yiisoft\Db\Schema\Data\LazyArray;
use Yiisoft\Db\Schema\Data\JsonLazyArray;
use Yiisoft\Db\Schema\Data\StringableStream;
use Yiisoft\Db\Schema\Data\StructuredLazyArray;
use Yiisoft\Db\Tests\Support\IntEnum;
use Yiisoft\Db\Tests\Support\Stringable;
use Yiisoft\Db\Tests\Support\StringEnum;

use function fclose;
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
            'datetime' => [DateTimeColumn::class, ColumnType::DATETIME, DateTimeImmutable::class],
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
                    [null, StringEnum::EMPTY],
                    [1, IntEnum::ONE],
                    [null, new Stringable('')],
                    [1, new Stringable('1')],
                    [1745071895, new DateTimeImmutable('2025-04-19 14:11:35')],
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
                    [null, StringEnum::EMPTY],
                    [1, IntEnum::ONE],
                    [null, new Stringable('')],
                    [1, new Stringable('1')],
                    [1745071895, new DateTimeImmutable('2025-04-19 14:11:35')],
                    ['12345678901234567890', '12345678901234567890'],
                    ['12345678901234567890', new Stringable('12345678901234567890')],
                    [$expression = new Expression('1'), $expression],
                ],
            ],
            'double' => [
                new DoubleColumn(),
                [
                    [null, null],
                    [null, ''],
                    [1.0, 1.0],
                    [1, 1],
                    [1.0, '1'],
                    [1.0, true],
                    [0.0, false],
                    [null, StringEnum::EMPTY],
                    [1, IntEnum::ONE],
                    [null, new Stringable('')],
                    [1.0, new Stringable('1')],
                    [1745071895.123456, new DateTimeImmutable('2025-04-19 14:11:35.123456')],
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
                    ['1', IntEnum::ONE],
                    ['', StringEnum::EMPTY],
                    ['one', StringEnum::ONE],
                    ['', new Stringable('')],
                    ['string', new Stringable('string')],
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
                    [new Param('1', PDO::PARAM_LOB), IntEnum::ONE],
                    [new Param('one', PDO::PARAM_LOB), StringEnum::ONE],
                    [new Param('string', PDO::PARAM_LOB), new Stringable('string')],
                    [$resource = fopen('php://memory', 'rb'), $resource],
                    [new Param($resource = fopen('php://memory', 'rb'), PDO::PARAM_LOB), new StringableStream($resource)],
                    [new Param("\x10\x11\x12", PDO::PARAM_LOB), new StringableStream("\x10\x11\x12")],
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
            'timestamp' => [
                new DateTimeColumn(ColumnType::TIMESTAMP, size: 0),
                [
                    [null, null],
                    [null, ''],
                    ['2025-04-19 00:00:00', '2025-04-19'],
                    ['2025-04-19 14:11:35', '2025-04-19 14:11:35'],
                    ['2025-04-19 14:11:35', '2025-04-19 14:11:35.123456'],
                    ['2025-04-19 12:11:35', '2025-04-19 14:11:35 +02:00'],
                    ['2025-04-19 12:11:35', '2025-04-19 14:11:35.123456 +02:00'],
                    ['2025-04-19 14:11:35', '1745071895'],
                    ['2025-04-19 14:11:35', '1745071895.123'],
                    ['2025-04-19 14:11:35', 1745071895],
                    ['2025-04-19 14:11:35', 1745071895.123],
                    ['2025-04-19 14:11:35', new DateTimeImmutable('2025-04-19 14:11:35')],
                    ['2025-04-19 14:11:35', new DateTime('2025-04-19 14:11:35')],
                    ['2025-04-19 14:11:35', new Stringable('2025-04-19 14:11:35')],
                    [$expression = new Expression("'2025-04-19 14:11:35'"), $expression],
                ],
            ],
            'timestamp6' => [
                new DateTimeColumn(ColumnType::TIMESTAMP, size: 6),
                [
                    [null, null],
                    [null, ''],
                    ['2025-04-19 00:00:00.000000', '2025-04-19'],
                    ['2025-04-19 14:11:35.000000', '2025-04-19 14:11:35'],
                    ['2025-04-19 14:11:35.123456', '2025-04-19 14:11:35.123456'],
                    ['2025-04-19 12:11:35.000000', '2025-04-19 14:11:35 +02:00'],
                    ['2025-04-19 12:11:35.123456', '2025-04-19 14:11:35.123456 +02:00'],
                    ['2025-04-19 14:11:35.000000', '1745071895'],
                    ['2025-04-19 14:11:35.123000', '1745071895.123'],
                    ['2025-04-19 14:11:35.000000', 1745071895],
                    ['2025-04-19 14:11:35.123000', 1745071895.123],
                    ['2025-04-19 14:11:35.123456', new DateTimeImmutable('2025-04-19 14:11:35.123456')],
                    ['2025-04-19 14:11:35.123456', new DateTime('2025-04-19 14:11:35.123456')],
                    ['2025-04-19 14:11:35.123456', new Stringable('2025-04-19 14:11:35.123456')],
                    [$expression = new Expression("'2025-04-19 14:11:35.123456'"), $expression],
                ],
            ],
            'datetime' => [
                new DateTimeColumn(size: 0),
                [
                    [null, null],
                    [null, ''],
                    ['2025-04-19 00:00:00', '2025-04-19'],
                    ['2025-04-19 14:11:35', '2025-04-19 14:11:35'],
                    ['2025-04-19 14:11:35', '2025-04-19 14:11:35.123456'],
                    ['2025-04-19 12:11:35', '2025-04-19 14:11:35 +02:00'],
                    ['2025-04-19 12:11:35', '2025-04-19 14:11:35.123456 +02:00'],
                    ['2025-04-19 14:11:35', '1745071895'],
                    ['2025-04-19 14:11:35', '1745071895.123'],
                    ['2025-04-19 14:11:35', 1745071895],
                    ['2025-04-19 14:11:35', 1745071895.123],
                    ['2025-04-19 14:11:35', new DateTimeImmutable('2025-04-19 14:11:35')],
                    ['2025-04-19 14:11:35', new DateTime('2025-04-19 14:11:35')],
                    ['2025-04-19 14:11:35', new Stringable('2025-04-19 14:11:35')],
                    [$expression = new Expression("'2025-04-19 14:11:35'"), $expression],
                ],
            ],
            'datetime6' => [
                new DateTimeColumn(size: 6),
                [
                    [null, null],
                    [null, ''],
                    ['2025-04-19 00:00:00.000000', '2025-04-19'],
                    ['2025-04-19 14:11:35.000000', '2025-04-19 14:11:35'],
                    ['2025-04-19 14:11:35.123456', '2025-04-19 14:11:35.123456'],
                    ['2025-04-19 12:11:35.000000', '2025-04-19 14:11:35 +02:00'],
                    ['2025-04-19 12:11:35.123456', '2025-04-19 14:11:35.123456 +02:00'],
                    ['2025-04-19 14:11:35.000000', '1745071895'],
                    ['2025-04-19 14:11:35.123000', '1745071895.123'],
                    ['2025-04-19 14:11:35.000000', 1745071895],
                    ['2025-04-19 14:11:35.123000', 1745071895.123],
                    ['2025-04-19 14:11:35.123456', new DateTimeImmutable('2025-04-19 14:11:35.123456')],
                    ['2025-04-19 14:11:35.123456', new DateTime('2025-04-19 14:11:35.123456')],
                    ['2025-04-19 14:11:35.123456', new Stringable('2025-04-19 14:11:35.123456')],
                    [$expression = new Expression("'2025-04-19 14:11:35.123456'"), $expression],
                ],
            ],
            'datetimetz' => [
                new DateTimeColumn(ColumnType::DATETIMETZ, size: 0),
                [
                    [null, null],
                    [null, ''],
                    ['2025-04-19 00:00:00+00:00', '2025-04-19'],
                    ['2025-04-19 14:11:35+00:00', '2025-04-19 14:11:35'],
                    ['2025-04-19 14:11:35+00:00', '2025-04-19 14:11:35.123456'],
                    ['2025-04-19 14:11:35+02:00', '2025-04-19 14:11:35 +02:00'],
                    ['2025-04-19 14:11:35+02:00', '2025-04-19 14:11:35.123456 +02:00'],
                    ['2025-04-19 14:11:35+00:00', '1745071895'],
                    ['2025-04-19 14:11:35+00:00', '1745071895.123'],
                    ['2025-04-19 14:11:35+00:00', 1745071895],
                    ['2025-04-19 14:11:35+00:00', 1745071895.123],
                    ['2025-04-19 14:11:35+02:00', new DateTimeImmutable('2025-04-19 14:11:35 +02:00')],
                    ['2025-04-19 14:11:35+02:00', new DateTime('2025-04-19 14:11:35 +02:00')],
                    ['2025-04-19 14:11:35+02:00', new Stringable('2025-04-19 14:11:35 +02:00')],
                    [$expression = new Expression("'2025-04-19 14:11:35 +02:00'"), $expression],
                ],
            ],
            'datetimetz6' => [
                new DateTimeColumn(ColumnType::DATETIMETZ, size: 6),
                [
                    [null, null],
                    [null, ''],
                    ['2025-04-19 00:00:00.000000+00:00', '2025-04-19'],
                    ['2025-04-19 14:11:35.000000+00:00', '2025-04-19 14:11:35'],
                    ['2025-04-19 14:11:35.123456+00:00', '2025-04-19 14:11:35.123456'],
                    ['2025-04-19 14:11:35.000000+02:00', '2025-04-19 14:11:35 +02:00'],
                    ['2025-04-19 14:11:35.123456+02:00', '2025-04-19 14:11:35.123456 +02:00'],
                    ['2025-04-19 14:11:35.000000+00:00', '1745071895'],
                    ['2025-04-19 14:11:35.123000+00:00', '1745071895.123'],
                    ['2025-04-19 14:11:35.000000+00:00', 1745071895],
                    ['2025-04-19 14:11:35.123000+00:00', 1745071895.123],
                    ['2025-04-19 14:11:35.123456+02:00', new DateTimeImmutable('2025-04-19 14:11:35.123456 +02:00')],
                    ['2025-04-19 14:11:35.123456+02:00', new DateTime('2025-04-19 14:11:35.123456 +02:00')],
                    ['2025-04-19 14:11:35.123456+02:00', new Stringable('2025-04-19 14:11:35.123456 +02:00')],
                    [$expression = new Expression("'2025-04-19 14:11:35.123456 +02:00'"), $expression],
                ],
            ],
            'time' => [
                new DateTimeColumn(ColumnType::TIME, size: 0),
                [
                    [null, null],
                    [null, ''],
                    ['00:00:00', '2025-04-19'],
                    ['14:11:35', '14:11:35'],
                    ['14:11:35', '14:11:35.123456'],
                    ['12:11:35', '14:11:35 +02:00'],
                    ['12:11:35', '14:11:35.123456 +02:00'],
                    ['14:11:35', '2025-04-19 14:11:35'],
                    ['14:11:35', '2025-04-19 14:11:35.123456'],
                    ['12:11:35', '2025-04-19 14:11:35 +02:00'],
                    ['12:11:35', '2025-04-19 14:11:35.123456 +02:00'],
                    ['14:11:35', '1745071895'],
                    ['14:11:35', '1745071895.123'],
                    ['14:11:35', 1745071895],
                    ['14:11:35', 1745071895.123],
                    ['14:11:35', 51095],
                    ['14:11:35', 51095.123456],
                    ['14:11:35', new DateTimeImmutable('14:11:35')],
                    ['14:11:35', new DateTime('14:11:35')],
                    ['14:11:35', new Stringable('14:11:35')],
                    [$expression = new Expression("'14:11:35'"), $expression],
                ],
            ],
            'time6' => [
                new DateTimeColumn(ColumnType::TIME, size: 6),
                [
                    [null, null],
                    [null, ''],
                    ['00:00:00.000000', '2025-04-19'],
                    ['14:11:35.000000', '14:11:35'],
                    ['14:11:35.123456', '14:11:35.123456'],
                    ['12:11:35.000000', '14:11:35 +02:00'],
                    ['12:11:35.123456', '14:11:35.123456 +02:00'],
                    ['14:11:35.000000', '2025-04-19 14:11:35'],
                    ['14:11:35.123456', '2025-04-19 14:11:35.123456'],
                    ['12:11:35.000000', '2025-04-19 14:11:35 +02:00'],
                    ['12:11:35.123456', '2025-04-19 14:11:35.123456 +02:00'],
                    ['14:11:35.000000', '1745071895'],
                    ['14:11:35.123000', '1745071895.123'],
                    ['14:11:35.000000', 1745071895],
                    ['14:11:35.123000', 1745071895.123],
                    ['14:11:35.000000', 51095],
                    ['14:11:35.123456', 51095.123456],
                    ['14:11:35.123456', new DateTimeImmutable('14:11:35.123456')],
                    ['14:11:35.123456', new DateTime('14:11:35.123456')],
                    ['14:11:35.123456', new Stringable('14:11:35.123456')],
                    [$expression = new Expression("'14:11:35.123456'"), $expression],
                ],
            ],
            'timetz' => [
                new DateTimeColumn(ColumnType::TIMETZ, size: 0),
                [
                    [null, null],
                    [null, ''],
                    ['00:00:00+00:00', '2025-04-19'],
                    ['14:11:35+00:00', '14:11:35'],
                    ['14:11:35+00:00', '14:11:35.123456'],
                    ['14:11:35+02:00', '14:11:35 +02:00'],
                    ['14:11:35+02:00', '14:11:35.123456 +02:00'],
                    ['14:11:35+00:00', '2025-04-19 14:11:35'],
                    ['14:11:35+00:00', '2025-04-19 14:11:35.123456'],
                    ['14:11:35+02:00', '2025-04-19 14:11:35 +02:00'],
                    ['14:11:35+02:00', '2025-04-19 14:11:35.123456 +02:00'],
                    ['14:11:35+00:00', '1745071895'],
                    ['14:11:35+00:00', '1745071895.123'],
                    ['14:11:35+00:00', 1745071895],
                    ['14:11:35+00:00', 1745071895.123],
                    ['14:11:35+00:00', 51095],
                    ['14:11:35+00:00', 51095.123456],
                    ['14:11:35+02:00', new DateTimeImmutable('14:11:35 +02:00')],
                    ['14:11:35+02:00', new DateTime('14:11:35 +02:00')],
                    ['14:11:35+02:00', new Stringable('14:11:35 +02:00')],
                    [$expression = new Expression("'14:11:35 +02:00'"), $expression],
                ],
            ],
            'timetz6' => [
                new DateTimeColumn(ColumnType::TIMETZ, size: 6),
                [
                    [null, null],
                    [null, ''],
                    ['00:00:00.000000+00:00', '2025-04-19'],
                    ['14:11:35.000000+00:00', '14:11:35'],
                    ['14:11:35.123456+00:00', '14:11:35.123456'],
                    ['14:11:35.000000+02:00', '14:11:35 +02:00'],
                    ['14:11:35.123456+02:00', '14:11:35.123456 +02:00'],
                    ['14:11:35.000000+00:00', '2025-04-19 14:11:35'],
                    ['14:11:35.123456+00:00', '2025-04-19 14:11:35.123456'],
                    ['14:11:35.000000+02:00', '2025-04-19 14:11:35 +02:00'],
                    ['14:11:35.123456+02:00', '2025-04-19 14:11:35.123456 +02:00'],
                    ['14:11:35.000000+00:00', '1745071895'],
                    ['14:11:35.123000+00:00', '1745071895.123'],
                    ['14:11:35.000000+00:00', 1745071895],
                    ['14:11:35.123000+00:00', 1745071895.123],
                    ['14:11:35.000000+00:00', 51095],
                    ['14:11:35.123456+00:00', 51095.123456],
                    ['14:11:35.123456+02:00', new DateTimeImmutable('14:11:35.123456 +02:00')],
                    ['14:11:35.123456+02:00', new DateTime('14:11:35.123456 +02:00')],
                    ['14:11:35.123456+02:00', new Stringable('14:11:35.123456 +02:00')],
                    [$expression = new Expression("'14:11:35.123456 +02:00'"), $expression],
                ],
            ],
            'date' => [
                new DateTimeColumn(ColumnType::DATE),
                [
                    [null, null],
                    [null, ''],
                    ['2025-04-19', '2025-04-19'],
                    ['2025-04-19', '2025-04-19 14:11:35'],
                    ['2025-04-19', '2025-04-19 14:11:35.123456'],
                    ['2025-04-19', '2025-04-19 14:11:35 +02:00'],
                    ['2025-04-19', '2025-04-19 14:11:35.123456 +02:00'],
                    ['2025-04-19', '1745071895'],
                    ['2025-04-19', '1745071895.123'],
                    ['2025-04-19', 1745071895],
                    ['2025-04-19', 1745071895.123],
                    ['2025-04-19', new DateTimeImmutable('2025-04-19 14:11:35')],
                    ['2025-04-19', new DateTime('2025-04-19 14:11:35')],
                    ['2025-04-19', new Stringable('2025-04-19 14:11:35')],
                    [$expression = new Expression("'2025-04-19'"), $expression],
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

    public static function dbTypecastColumnsWithException(): array
    {
        $resource = fopen('php://memory', 'rb');
        fclose($resource);

        return [
            'integer array' => [new IntegerColumn(), []],
            'integer resource' => [new IntegerColumn(), fopen('php://memory', 'r')],
            'integer stdClass' => [new IntegerColumn(), new stdClass()],
            'bigint array' => [new BigIntColumn(), []],
            'bigint resource' => [new BigIntColumn(), fopen('php://memory', 'r')],
            'bigint stdClass' => [new BigIntColumn(), new stdClass()],
            'double array' => [new DoubleColumn(), []],
            'double resource' => [new DoubleColumn(), fopen('php://memory', 'r')],
            'double stdClass' => [new DoubleColumn(), new stdClass()],
            'string array' => [new StringColumn(), []],
            'string stdClass' => [new StringColumn(), new stdClass()],
            'binary closed' => [new BinaryColumn(), $resource],
            'binary array' => [new BinaryColumn(), []],
            'binary stdClass' => [new BinaryColumn(), new stdClass()],
            'datetime array' => [new DateTimeColumn(), []],
            'datetime resource' => [new DateTimeColumn(), fopen('php://memory', 'r')],
            'datetime enum' => [new DateTimeColumn(), StringEnum::ONE],
            'datetime stdClass' => [new DateTimeColumn(), new stdClass()],
        ];
    }

    public static function phpTypecastColumns(): array
    {
        $utcTimezone = new DateTimeZone('UTC');

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
                    [new StringableStream($resource = fopen('php://memory', 'rb')), $resource],
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
            'timestamp' => [
                new DateTimeColumn(ColumnType::TIMESTAMP),
                [
                    [null, null],
                    [new DateTimeImmutable('2025-04-19 14:11:35.123456', $utcTimezone), '2025-04-19 14:11:35.123456'],
                ],
            ],
            'datetime' => [
                new DateTimeColumn(),
                [
                    [null, null],
                    [new DateTimeImmutable('2025-04-19 14:11:35.123456', $utcTimezone), '2025-04-19 14:11:35.123456'],
                ],
            ],
            'datetimetz' => [
                new DateTimeColumn(ColumnType::DATETIMETZ),
                [
                    [null, null],
                    [new DateTimeImmutable('2025-04-19 14:11:35.123456 +02:00'), '2025-04-19 14:11:35.123456+02:00'],
                ],
            ],
            'time' => [
                new DateTimeColumn(ColumnType::TIME),
                [
                    [null, null],
                    [new DateTimeImmutable('14:11:35.123456', $utcTimezone), '14:11:35.123456'],
                ],
            ],
            'timetz' => [
                new DateTimeColumn(ColumnType::TIMETZ),
                [
                    [null, null],
                    [new DateTimeImmutable('14:11:35.123456 +02:00'), '14:11:35.123456+02:00'],
                ],
            ],
            'date' => [
                new DateTimeColumn(ColumnType::DATE),
                [
                    [null, null],
                    [new DateTimeImmutable('2025-04-19', $utcTimezone), '2025-04-19'],
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
            ColumnType::DATETIME => [
                new DateTimeColumn(),
                [
                    [1, [
                        '2025-04-19 14:11:35',
                        '2025-04-19 14:11:35',
                        '2025-04-19 14:11:35',
                        null,
                    ], [
                        '2025-04-19 14:11:35',
                        new DateTime('2025-04-19 14:11:35'),
                        new DateTimeImmutable('2025-04-19 14:11:35'),
                        null,
                    ]],
                    [2, [[
                        '2025-04-19 14:11:35',
                        '2025-04-19 14:11:35',
                        '2025-04-19 14:11:35',
                        null,
                    ]], [[
                        '2025-04-19 14:11:35',
                        new DateTime('2025-04-19 14:11:35'),
                        new DateTimeImmutable('2025-04-19 14:11:35'),
                        null,
                    ]]],
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
            ['reference', $fk = new ForeignKey(), 'getReference', $fk],
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

    public static function dateTimeColumn(): array
    {
        return [
            'date and time' => [
                '2025-04-19 14:11:35',
                [
                    'timestamp' => '2025-04-19 14:11:35',
                    'datetime' => '2025-04-19 14:11:35',
                    'datetime3' => '2025-04-19 14:11:35',
                    'datetimetz' => '2025-04-19 14:11:35',
                    'datetimetz6' => '2025-04-19 14:11:35',
                    'time' => '14:11:35',
                    'time3' => '14:11:35',
                    'timetz' => '14:11:35',
                    'timetz6' => '14:11:35',
                    'date' => '2025-04-19',
                ],
            ],
            'date, time and milliseconds' => [
                '2025-04-19 14:11:35.123456',
                [
                    'timestamp' => '2025-04-19 14:11:35',
                    'datetime' => '2025-04-19 14:11:35',
                    'datetime3' => '2025-04-19 14:11:35.123',
                    'datetimetz' => '2025-04-19 14:11:35',
                    'datetimetz6' => '2025-04-19 14:11:35.123456',
                    'time' => '14:11:35',
                    'time3' => '14:11:35.123',
                    'timetz' => '14:11:35',
                    'timetz6' => '14:11:35.123456',
                    'date' => '2025-04-19',
                ],
            ],
            'date, time, milliseconds and timezone' => [
                '2025-04-19 14:11:35.123456 +02:00',
                [
                    'timestamp' => '2025-04-19 12:11:35',
                    'datetime' => '2025-04-19 12:11:35',
                    'datetime3' => '2025-04-19 12:11:35.123',
                    'datetimetz' => '2025-04-19 14:11:35 +02:00',
                    'datetimetz6' => '2025-04-19 14:11:35.123456 +02:00',
                    'time' => '12:11:35',
                    'time3' => '12:11:35.123',
                    'timetz' => '14:11:35 +02:00',
                    'timetz6' => '14:11:35.123456 +02:00',
                    'date' => '2025-04-19',
                ],
            ],
            'integer' => [
                1745071895,
                [
                    'timestamp' => '2025-04-19 14:11:35',
                    'datetime' => '2025-04-19 14:11:35',
                    'datetime3' => '2025-04-19 14:11:35',
                    'datetimetz' => '2025-04-19 14:11:35',
                    'datetimetz6' => '2025-04-19 14:11:35',
                    'time' => '14:11:35',
                    'time3' => '14:11:35',
                    'timetz' => '14:11:35',
                    'timetz6' => '14:11:35',
                    'date' => '2025-04-19',
                ],
            ],
            'float' => [
                1745071895.123,
                [
                    'timestamp' => '2025-04-19 14:11:35',
                    'datetime' => '2025-04-19 14:11:35',
                    'datetime3' => '2025-04-19 14:11:35.123',
                    'datetimetz' => '2025-04-19 14:11:35',
                    'datetimetz6' => '2025-04-19 14:11:35.123',
                    'time' => '14:11:35',
                    'time3' => '14:11:35.123',
                    'timetz' => '14:11:35',
                    'timetz6' => '14:11:35.123',
                    'date' => '2025-04-19',
                ],
            ],
        ];
    }
}
