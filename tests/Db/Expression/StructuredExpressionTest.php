<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Expression;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Expression\StructuredExpression;
use Yiisoft\Db\Schema\Column\ColumnBuilder;
use Yiisoft\Db\Tests\Support\TestTrait;

/**
 * @group pgsql
 */
final class StructuredExpressionTest extends TestCase
{
    use TestTrait;

    public function testConstruct(): void
    {
        $columns = [
            'value' => ColumnBuilder::money(10, 2),
            'currency' => ColumnBuilder::char(3),
        ];

        $expression = new StructuredExpression([5, 'USD'], 'currency_money_structured', $columns);

        $this->assertSame([5, 'USD'], $expression->getValue());
        $this->assertSame('currency_money_structured', $expression->getType());
        $this->assertSame($columns, $expression->getColumns());
    }

    /** @dataProvider \Yiisoft\Db\Tests\Provider\StructuredTypeProvider::normolizedValues */
    public function testGetNormalizedValue(mixed $value, mixed $expected, array $columns): void
    {
        $expression = new StructuredExpression($value, 'currency_money_structured', $columns);

        $this->assertSame($expected, $expression->getNormalizedValue());
    }
}
