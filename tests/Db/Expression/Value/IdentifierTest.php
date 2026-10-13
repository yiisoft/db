<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Expression\Value;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Expression\Value\Identifier;

final class IdentifierTest extends TestCase
{
    public static function dataParts(): iterable
    {
        yield 'column' => ['name', ['name']];
        yield 'table and column' => ['t.name', ['t', 'name']];
        yield 'schema, table and column' => ['s.t.name', ['s', 't', 'name']];
        yield 'special characters' => ['LOWER(name)', ['LOWER(name)']];
        yield 'array' => [['t', 'name'], ['t', 'name']];
        yield 'array with dot' => [['t', 'na.me'], ['t', 'na.me']];
        yield 'array with single part' => [['na.me'], ['na.me']];
    }

    #[DataProvider('dataParts')]
    public function testParts(string|array $name, array $expected): void
    {
        $identifier = new Identifier($name);

        $this->assertSame($expected, $identifier->parts);
    }

    public static function dataEmpty(): iterable
    {
        yield 'empty string' => [''];
        yield 'empty array' => [[]];
    }

    #[DataProvider('dataEmpty')]
    public function testEmpty(string|array $name): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Identifier must not be empty.');

        new Identifier($name);
    }

    public static function dataEmptyPart(): iterable
    {
        yield 'double dot' => ['a..b'];
        yield 'leading dot' => ['.a'];
        yield 'trailing dot' => ['a.'];
        yield 'dot only' => ['.'];
        yield 'empty array part' => [['t', '']];
        yield 'array with empty string' => [['']];
    }

    #[DataProvider('dataEmptyPart')]
    public function testEmptyPart(string|array $name): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Identifier parts must not be empty.');

        new Identifier($name);
    }
}
