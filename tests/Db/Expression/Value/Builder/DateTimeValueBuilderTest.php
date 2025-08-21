<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Expression\Value\Builder;

use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Expression\Value\Builder\DateTimeValueBuilder;
use Yiisoft\Db\Expression\Value\DateTimeType;
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
            new DateTimeImmutable('2023-12-25 15:30:45', new DateTimeZone('+02:00')),
            DateTimeType::DateTimeTz,
            '2023-12-25 15:30:45+02:00'
        ];
        yield 'DateTimeTz with microseconds' => [
            new DateTimeImmutable('2023-12-25 15:30:45.123456', new DateTimeZone('+02:00')),
            DateTimeType::DateTimeTz,
            '2023-12-25 15:30:45.123456+02:00'
        ];
        yield 'DateTime without microseconds' => [
            new DateTimeImmutable('2023-12-25 15:30:45'),
            DateTimeType::DateTime,
            '2023-12-25 15:30:45'
        ];
        yield 'DateTime with microseconds' => [
            new DateTimeImmutable('2023-12-25 15:30:45.123456'),
            DateTimeType::DateTime,
            '2023-12-25 15:30:45.123456'
        ];
        yield 'Date' => [
            new DateTimeImmutable('2023-12-25 15:30:45'),
            DateTimeType::Date,
            '2023-12-25'
        ];
        yield 'TimeTz without microseconds' => [
            new DateTimeImmutable('2023-12-25 15:30:45', new DateTimeZone('+02:00')),
            DateTimeType::TimeTz,
            '15:30:45+02:00'
        ];
        yield 'TimeTz with microseconds' => [
            new DateTimeImmutable('2023-12-25 15:30:45.123456', new DateTimeZone('+02:00')),
            DateTimeType::TimeTz,
            '15:30:45.123456+02:00'
        ];
        yield 'Time without microseconds' => [
            new DateTimeImmutable('2023-12-25 15:30:45'),
            DateTimeType::Time,
            '15:30:45'
        ];
        yield 'Time with microseconds' => [
            new DateTimeImmutable('2023-12-25 15:30:45.123456'),
            DateTimeType::Time,
            '15:30:45.123456'
        ];
        yield 'Timestamp' => [
            new DateTimeImmutable('2023-12-25 15:30:45+2:00'),
            DateTimeType::Timestamp,
            '1703511045'
        ];
    }

    #[DataProvider('dataBuild')]
    public function testBuild(\DateTimeInterface $dateTime, DateTimeType $type, string $expectedFormat): void
    {
        $builder = new DateTimeValueBuilder(
            $this->getConnection()->getQueryBuilder()
        );
        $expression = new DateTimeValue($dateTime, $type);

        $params = [];
        $result = $builder->build($expression, $params);

        assertSame(':qp0', $result);
        assertSame([':qp0' => $expectedFormat], $params);
    }
}
