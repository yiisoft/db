<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Expression\Value;

use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Expression\Value\DateTimeValue;

final class DateTimeValueTest extends TestCase
{
    public static function types(): array
    {
        return [
            [ColumnType::DATE],
            [ColumnType::DATETIME],
            [ColumnType::DATETIMETZ],
            [ColumnType::TIME],
            [ColumnType::TIMETZ],
            [ColumnType::TIMESTAMP],
            [ColumnType::BIGINT],
            [ColumnType::INTEGER],
            [ColumnType::FLOAT],
            [ColumnType::DOUBLE],
            [ColumnType::DECIMAL],
        ];
    }

    public function testDefaults(): void
    {
        $expression = new DateTimeValue(new DateTimeImmutable());

        $this->assertSame(ColumnType::DATETIMETZ, $expression->type);
        $this->assertSame([], $expression->info);
    }

    #[DataProvider('types')]
    public function testConstructWithType(string $type): void
    {
        $expression = new DateTimeValue(new DateTimeImmutable(), $type);

        $this->assertSame($type, $expression->type);
    }

    public function testConstructWithUnsupportedType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("The type '" . ColumnType::BOOLEAN . "' is not supported by DateTimeValue.");

        new DateTimeValue(new DateTimeImmutable(), ColumnType::BOOLEAN);
    }
}
