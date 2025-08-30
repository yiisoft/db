<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Expression\Value;

use ArrayIterator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Expression\Value\StructuredExpression;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Schema\Column\AbstractStructuredColumn;
use Yiisoft\Db\Schema\Column\ColumnBuilder;
use Yiisoft\Db\Schema\Data\JsonLazyArray;
use Yiisoft\Db\Tests\Support\TestTrait;

/**
 * @group db
 */
final class StructuredExpressionTest extends TestCase
{
    use TestTrait;

    public static function constructProvider(): array
    {
        $column = ColumnBuilder::structured('currency_money_structured', [
            'value' => ColumnBuilder::money(10, 2),
            'currency' => ColumnBuilder::char(3),
        ]);

        return [
            [[5, 'USD'], null],
            [['value' => 5, 'currency' => 'USD'], null],
            [new ArrayIterator([5, 'USD']), $column],
            [new Query(self::getDb()), 'currency_money_structured'],
            [new JsonLazyArray('[5,"USD"]'), null],
            [(object) ['value' => 5, 'currency' => 'USD'], null],
        ];
    }

    #[DataProvider('constructProvider')]
    public function testConstruct(
        array|object|string $value,
        AbstractStructuredColumn|string|null $type = null
    ): void {
        $expression = new StructuredExpression($value, $type);

        $this->assertSame($value, $expression->getValue());
        $this->assertSame($type, $expression->getType());
    }
}
