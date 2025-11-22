<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Schema\Column;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Schema\Column\EnumColumn;
use Yiisoft\Db\Tests\Support\IntEnum;
use Yiisoft\Db\Tests\Support\Stringable;
use Yiisoft\Db\Tests\Support\StringEnum;

final class EnumColumnTest extends TestCase
{
    public function testEnumValues(): void
    {
        $column = new EnumColumn();

        $this->assertNull($column->getEnumValues());
        $this->assertSame($column, $column->enumValues(['positive', 'negative']));
        $this->assertSame(['positive', 'negative'], $column->getEnumValues());

        $column->enumValues([]);

        $this->assertSame([], $column->getEnumValues());
    }

    public static function dataDbTypecast(): iterable
    {
        yield [null, null];
        yield ['1', 1];
        yield ['one', 'one'];
        yield ['1', 1.0];
        yield ['1.2', 1.2];
        yield ['', StringEnum::EMPTY];
        yield ['one', StringEnum::ONE];
        yield ['1', IntEnum::ONE];
        yield ['1', true];
        yield ['0', false];
        yield ['', new Stringable('')];
        yield ['string', new Stringable('string')];

        $resource = fopen('php://memory', 'rb');
        yield [$resource, $resource];

        $expression = new Expression('expression');
        yield [$expression, $expression];
    }

    #[DataProvider('dataDbTypecast')]
    public function testDbTypecast(mixed $expected, mixed $value): void
    {
        $column = new EnumColumn();

        $result = $column->dbTypecast($value);

        $this->assertSame($expected, $result);
    }

    public static function dataPhpTypecast(): iterable
    {
        yield [null, null];
        yield ['', ''];
        yield ['one', 'one'];
    }

    #[DataProvider('dataPhpTypecast')]
    public function testPhpTypecast(mixed $expected, mixed $value): void
    {
        $column = new EnumColumn();

        $result = $column->phpTypecast($value);

        $this->assertSame($expected, $result);
    }
}
