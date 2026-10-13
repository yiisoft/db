<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Expression\Value;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Stringable;
use Yiisoft\Db\Expression\Value\UuidValue;
use Yiisoft\Db\Tests\Support\Stringable as StringableObject;

use function hex2bin;

final class UuidValueTest extends TestCase
{
    private const UUID = '738146be-87b1-49f2-9913-36142fb6fcbe';

    public static function values(): iterable
    {
        yield 'canonical' => [self::UUID];
        yield 'canonical in upper case' => ['738146BE-87B1-49F2-9913-36142FB6FCBE'];
        yield 'hexadecimal' => ['738146be87b149f2991336142fb6fcbe'];
        yield 'hexadecimal in upper case' => ['738146BE87B149F2991336142FB6FCBE'];
        yield 'bytes' => [hex2bin('738146be87b149f2991336142fb6fcbe')];
        yield 'stringable' => [new StringableObject(self::UUID)];
    }

    #[DataProvider('values')]
    public function testValueIsNormalized(string|Stringable $value): void
    {
        $expression = new UuidValue($value);

        $this->assertSame(self::UUID, $expression->value);
    }

    public static function invalidValues(): iterable
    {
        yield 'empty' => [''];
        yield 'misplaced dash' => ['738146be-87b149f2-9913-36142fb6fcbe'];
        yield 'not hexadecimal' => ['738146be-87b1-K9f2-9913-36142fb6fcbe'];
        yield 'wrong separator' => ['738146be+87b1-49f2-9913-36142fb6fcbe'];
        yield 'too short' => ['738146be87b149f2991336142fb6fcb'];
        yield '32 characters but not hexadecimal' => ['zzz146be87b149f2991336142fb6fcbe'];
    }

    #[DataProvider('invalidValues')]
    public function testConstructWithInvalidValue(string $value): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Value is not a valid UUID. Expected the canonical form, 32 hexadecimal characters or 16 raw bytes.',
        );

        new UuidValue($value);
    }
}
