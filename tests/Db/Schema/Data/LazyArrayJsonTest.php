<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Schema\Data;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Schema\Data\LazyArrayJson;

use function iterator_to_array;

/**
 * @group db
 */
final class LazyArrayJsonTest extends TestCase
{
    public static function valueProvider(): array
    {
        return [
            ['[]', []],
            ['{}', []],
            ['[1,2,3]', [1, 2, 3]],
            ['{"a":1,"b":null}', ['a' => 1, 'b' => null]],
        ];
    }

    #[DataProvider('valueProvider')]
    public function testGetValue(string $value, array $expected): void
    {
        $lazyArray = new LazyArrayJson($value);

        $this->assertSame($value, $lazyArray->getRawValue());
        $this->assertSame($expected, $lazyArray->getValue());
        $this->assertSame($expected, $lazyArray->getRawValue());
    }

    #[DataProvider('valueProvider')]
    public function testJsonSerialize(string $value, array $expected): void
    {
        $lazyArray = new LazyArrayJson($value);

        $this->assertSame($expected, $lazyArray->jsonSerialize());
    }

    public function testOffset(): void
    {
        $lazyArray = new LazyArrayJson('[1,2,3]');

        $this->assertTrue(isset($lazyArray[0]));
        $this->assertFalse(isset($lazyArray[3]));
        $this->assertSame(1, $lazyArray[0]);

        $lazyArray[0] = 10;
        $lazyArray[3] = 4;

        $this->assertSame(10, $lazyArray[0]);
        $this->assertTrue(isset($lazyArray[3]));
        $this->assertSame(4, $lazyArray[3]);

        unset($lazyArray[0]);

        $this->assertFalse(isset($lazyArray[0]));
    }

    public function testCount(): void
    {
        $lazyArray = new LazyArrayJson('[]');

        $this->assertCount(0, $lazyArray);

        $lazyArray = new LazyArrayJson('[1,2,3]');

        $this->assertCount(3, $lazyArray);
    }

    #[DataProvider('valueProvider')]
    public function testIterator(string $value, array $expected): void
    {
        $lazyArray = new LazyArrayJson($value);

        $this->assertSame($expected, iterator_to_array($lazyArray));
    }

    public function testNullValue()
    {
        $lazyArray = new LazyArrayJson('null');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('JSON value must be a valid string array representation.');

        $lazyArray->getValue();
    }
}
