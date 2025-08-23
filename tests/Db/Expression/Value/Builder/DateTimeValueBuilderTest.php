<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Expression\Value\Builder;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Constant\DataType;
use Yiisoft\Db\Expression\Param;
use Yiisoft\Db\Expression\Value\Builder\DateTimeValueBuilder;
use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Expression\Value\DateTimeValue;
use Yiisoft\Db\Tests\Support\TestTrait;

/**
 * @group db
 */
final class DateTimeValueBuilderTest extends TestCase
{
    use TestTrait;

    public static function dataBuild(): iterable
    {
        yield 'DateTimeTz without microseconds' => [
            ':qp0',
            [':qp0' => new Param('2023-12-25 15:30:45+02:00', DataType::STRING)],
            new DateTimeValue(
                new DateTimeImmutable('2023-12-25 15:30:45.123456', new DateTimeZone('+02:00')),
                ColumnType::DATETIMETZ,
            ),
        ];
        yield 'DateTimeTz with microseconds' => [
            ':qp0',
            [':qp0' => new Param('2023-12-25 15:30:45.123456+02:00', DataType::STRING)],
            new DateTimeValue(
                new DateTimeImmutable('2023-12-25 15:30:45.123456', new DateTimeZone('+02:00')),
                ColumnType::DATETIMETZ,
                ['size' => 6],
            ),
        ];
        yield 'DateTimeTz with milliseconds' => [
            ':qp0',
            [':qp0' => new Param('2023-12-25 15:30:45.123+02:00', DataType::STRING)],
            new DateTimeValue(
                new DateTimeImmutable('2023-12-25 15:30:45.123456', new DateTimeZone('+02:00')),
                ColumnType::DATETIMETZ,
                ['size' => 1],
            ),
        ];
        yield 'DateTime without microseconds' => [
            ':qp0',
            [':qp0' => new Param('2023-12-25 15:30:45', DataType::STRING)],
            new DateTimeValue(
                new DateTimeImmutable('2023-12-25 15:30:45.123456'),
                ColumnType::DATETIME,
            ),
        ];
        yield 'DateTime with microseconds' => [
            ':qp0',
            [':qp0' => new Param('2023-12-25 15:30:45.123456', DataType::STRING)],
            new DateTimeValue(
                new DateTimeImmutable('2023-12-25 15:30:45.123456'),
                ColumnType::DATETIME,
                ['size' => 6],
            ),
        ];
        yield 'DateTime with milliseconds' => [
            ':qp0',
            [':qp0' => new Param('2023-12-25 15:30:45.123', DataType::STRING)],
            new DateTimeValue(
                new DateTimeImmutable('2023-12-25 15:30:45.123456'),
                ColumnType::DATETIME,
                ['size' => 3],
            ),
        ];
        yield 'Date' => [
            ':qp0',
            [':qp0' => new Param('2023-12-25', DataType::STRING)],
            new DateTimeValue(
                new DateTimeImmutable('2023-12-25 15:30:45'),
                ColumnType::DATE,
            ),
        ];
        yield 'TimeTz without microseconds' => [
            ':qp0',
            [':qp0' => new Param('15:30:45+02:00', DataType::STRING)],
            new DateTimeValue(
                new DateTimeImmutable('2023-12-25 15:30:45.123456', new DateTimeZone('+02:00')),
                ColumnType::TIMETZ,
            ),
        ];
        yield 'TimeTz with microseconds' => [
            ':qp0',
            [':qp0' => new Param('15:30:45.123456+02:00', DataType::STRING)],
            new DateTimeValue(
                new DateTimeImmutable('2023-12-25 15:30:45.123456', new DateTimeZone('+02:00')),
                ColumnType::TIMETZ,
                ['size' => 6],
            ),
        ];
        yield 'TimeTz with milliseconds' => [
            ':qp0',
            [':qp0' => new Param('15:30:45.123+02:00', DataType::STRING)],
            new DateTimeValue(
                new DateTimeImmutable('2023-12-25 15:30:45.123456', new DateTimeZone('+02:00')),
                ColumnType::TIMETZ,
                ['size' => 3],
            ),
        ];
        yield 'Time without microseconds' => [
            ':qp0',
            [':qp0' => new Param('15:30:45', DataType::STRING)],
            new DateTimeValue(
                new DateTimeImmutable('2023-12-25 15:30:45.123456'),
                ColumnType::TIME,
            ),
        ];
        yield 'Time with microseconds' => [
            ':qp0',
            [':qp0' => new Param('15:30:45.123456', DataType::STRING)],
            new DateTimeValue(
                new DateTimeImmutable('2023-12-25 15:30:45.123456'),
                ColumnType::TIME,
                ['size' => 6],
            ),
        ];
        yield 'Time with milliseconds' => [
            ':qp0',
            [':qp0' => new Param('15:30:45.123', DataType::STRING)],
            new DateTimeValue(
                new DateTimeImmutable('2023-12-25 15:30:45.123456'),
                ColumnType::TIME,
                ['size' => 2],
            ),
        ];
        yield 'Timestamp' => [
            ':qp0',
            [':qp0' => new Param('2023-12-25 13:30:45', DataType::STRING)],
            new DateTimeValue(
                new DateTimeImmutable('2023-12-25 15:30:45+2:00'),
                ColumnType::TIMESTAMP,
            ),
        ];
        yield 'Integer' => [
            '1703511045',
            [],
            new DateTimeValue(
                new DateTimeImmutable('2023-12-25 15:30:45+2:00'),
                ColumnType::INTEGER,
            ),
        ];
    }

    #[DataProvider('dataBuild')]
    public function testBuild(string $expectedResult, array $expectedParams, DateTimeValue $value): void
    {
        $builder = new DateTimeValueBuilder(
            $this->getConnection()->getQueryBuilder()
        );

        $params = [];
        $result = $builder->build($value, $params);

        $this->assertSame($expectedResult, $result);
        $this->assertEquals($expectedParams, $params);
    }
}
