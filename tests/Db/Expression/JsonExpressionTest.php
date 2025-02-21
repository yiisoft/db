<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Expression;

use ArrayIterator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Expression\JsonExpression;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Schema\Data\LazyArrayJson;
use Yiisoft\Db\Tests\Support\TestTrait;

/**
 * @group db
 */
final class JsonExpressionTest extends TestCase
{
    use TestTrait;

    public static function constructProvider(): array
    {
        return [
            [['a', 'b', 'c'], null],
            [new ArrayIterator(['a', 'b', 'c']), 'json'],
            [new Query(self::getDb()), 'jsonb'],
            [new LazyArrayJson('[1,2,3]'), null],
            ['[1,2,3]', null],
            ['{"a":1,"b":2}', null],
            [1, null],
            ['', null],
            [null, null],
        ];
    }

    #[DataProvider('constructProvider')]
    public function testConstruct(
        mixed $value,
        string|null $type = null
    ): void {
        $expression = new JsonExpression($value, $type);

        $this->assertSame($value, $expression->getValue());
        $this->assertSame($type, $expression->getType());
    }
}
