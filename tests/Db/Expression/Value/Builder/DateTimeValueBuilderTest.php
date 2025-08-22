<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Expression\Value\Builder;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Expression\Value\Builder\DateTimeValueBuilder;
use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Expression\Value\DateTimeValue;
use Yiisoft\Db\Tests\Support\TestTrait;

use function PHPUnit\Framework\assertSame;

/**
 * @group db
 */
final class DateTimeValueBuilderTest extends TestCase
{
    use TestTrait;

    public static function dataBuild(): iterable
    {
        yield 'DateTimeTz without microseconds' => [
            '2023-12-25 15:30:45+02:00',
            new DateTimeValue(
                new DateTimeImmutable('2023-12-25 15:30:45.123456', new DateTimeZone('+02:00')),
                ColumnType::DATETIMETZ,
            ),
        ];
        yield 'DateTimeTz with microseconds' => [
            '2023-12-25 15:30:45.123456+02:00',
            new DateTimeValue(
                new DateTimeImmutable('2023-12-25 15:30:45.123456', new DateTimeZone('+02:00')),
                ColumnType::DATETIMETZ,
                6
            ),
        ];
        yield 'DateTimeTz with milliseconds' => [
            '2023-12-25 15:30:45.123+02:00',
            new DateTimeValue(
                new DateTimeImmutable('2023-12-25 15:30:45.123456', new DateTimeZone('+02:00')),
                ColumnType::DATETIMETZ,
                1,
            ),
        ];
        yield 'DateTime without microseconds' => [
            '2023-12-25 15:30:45',
            new DateTimeValue(
                new DateTimeImmutable('2023-12-25 15:30:45.123456'),
                ColumnType::DATETIME,
            ),
        ];
        yield 'DateTime with microseconds' => [
            '2023-12-25 15:30:45.123456',
            new DateTimeValue(
                new DateTimeImmutable('2023-12-25 15:30:45.123456'),
                ColumnType::DATETIME,
                6,
            ),
        ];
        yield 'DateTime with milliseconds' => [
            '2023-12-25 15:30:45.123',
            new DateTimeValue(
                new DateTimeImmutable('2023-12-25 15:30:45.123456'),
                ColumnType::DATETIME,
                3
            ),
        ];
        yield 'Date' => [
            '2023-12-25',
            new DateTimeValue(
                new DateTimeImmutable('2023-12-25 15:30:45'),
                ColumnType::DATE,
            ),
        ];
        yield 'TimeTz without microseconds' => [
            '15:30:45+02:00',
            new DateTimeValue(
                new DateTimeImmutable('2023-12-25 15:30:45.123456', new DateTimeZone('+02:00')),
                ColumnType::TIMETZ,
            ),
        ];
        yield 'TimeTz with microseconds' => [
            '15:30:45.123456+02:00',
            new DateTimeValue(
                new DateTimeImmutable('2023-12-25 15:30:45.123456', new DateTimeZone('+02:00')),
                ColumnType::TIMETZ,
                6,
            ),
        ];
        yield 'TimeTz with milliseconds' => [
            '15:30:45.123+02:00',
            new DateTimeValue(
                new DateTimeImmutable('2023-12-25 15:30:45.123456', new DateTimeZone('+02:00')),
                ColumnType::TIMETZ,
                3,
            ),
        ];
        yield 'Time without microseconds' => [
            '15:30:45',
            new DateTimeValue(
                new DateTimeImmutable('2023-12-25 15:30:45.123456'),
                ColumnType::TIME,
            ),
        ];
        yield 'Time with microseconds' => [
            '15:30:45.123456',
            new DateTimeValue(
                new DateTimeImmutable('2023-12-25 15:30:45.123456'),
                ColumnType::TIME,
                6,
            ),
        ];
        yield 'Time with milliseconds' => [
            '15:30:45.123',
            new DateTimeValue(
                new DateTimeImmutable('2023-12-25 15:30:45.123456'),
                ColumnType::TIME,
                2,
            ),
        ];
        yield 'Timestamp' => [
            '2023-12-25 13:30:45',
            new DateTimeValue(
                new DateTimeImmutable('2023-12-25 15:30:45+2:00'),
                ColumnType::TIMESTAMP,
            ),
        ];
        yield 'Integer' => [
            1703511045,
            new DateTimeValue(
                new DateTimeImmutable('2023-12-25 15:30:45+2:00'),
                ColumnType::INTEGER,
            ),
        ];
        yield 'Float' => [
            1703511045.112233,
            new DateTimeValue(
                new DateTimeImmutable('2023-12-25 15:30:45.112233+2:00'),
                ColumnType::FLOAT,
            ),
        ];
    }

    #[DataProvider('dataBuild')]
    public function testBuild(mixed $expected, DateTimeValue $value): void
    {
        $builder = new DateTimeValueBuilder(
            $this->getConnection()->getQueryBuilder()
        );

        $params = [];
        $result = $builder->build($value, $params);

        assertSame(':qp0', $result);
        assertSame([':qp0' => $expected], $params);
    }
}
