<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Expression\Value;

use ArrayIterator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Expression\Value\ArrayValue;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Query\QueryInterface;
use Yiisoft\Db\Schema\Column\ArrayColumn;
use Yiisoft\Db\Schema\Column\ColumnInterface;
use Yiisoft\Db\Schema\Column\IntegerColumn;
use Yiisoft\Db\Schema\Data\LazyArrayInterface;
use Yiisoft\Db\Schema\Data\JsonLazyArray;
use Yiisoft\Db\Tests\Support\TestTrait;

/**
 * @group db
 */
final class ArrayValueTest extends TestCase
{
    use TestTrait;

    public static function constructProvider(): array
    {
        return [
            [['a', 'b', 'c'], null],
            [new ArrayIterator(['a', 'b', 'c']), 'integer[]'],
            [new Query(self::getDb()), new ArrayColumn()],
            [new JsonLazyArray('[1,2,3]'), new IntegerColumn()],
        ];
    }

    #[DataProvider('constructProvider')]
    public function testConstruct(
        iterable|LazyArrayInterface|QueryInterface|string $value,
        ColumnInterface|string|null $type = null
    ): void {
        $arrayValue = new ArrayValue($value, $type);

        $this->assertSame($value, $arrayValue->value);
        $this->assertSame($type, $arrayValue->type);
    }
}
