<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Expression\Value\Builder;

use ArrayIterator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Expression\Value\Param;
use Yiisoft\Db\Constant\DataType;
use Yiisoft\Db\Expression\Value\StructuredValue;
use Yiisoft\Db\Expression\Value\Builder\StructuredValueBuilder;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Schema\Column\AbstractStructuredColumn;
use Yiisoft\Db\Schema\Column\ColumnBuilder;
use Yiisoft\Db\Schema\Data\JsonLazyArray;
use Yiisoft\Db\Schema\Data\StructuredLazyArray;
use Yiisoft\Db\Tests\Support\TestTrait;

/**
 * @group db
 */
final class StructuredValueBuilderTest extends TestCase
{
    use TestTrait;

    public static function buildProvider(): array
    {
        $column = ColumnBuilder::structured(columns: [
            'value' => ColumnBuilder::integer(),
            'currency_code' => ColumnBuilder::string()->defaultValue('USD'),
        ]);

        return [
            [[5, 'USD'], null, '[5,"USD"]'],
            [new ArrayIterator(['5', 'USD']), $column, '["5","USD"]'],
            [new StructuredLazyArray('["5","USD"]'), $column, '["5","USD"]'],
            [new JsonLazyArray('["5","USD"]'), $column, '["5","USD"]'],
            [['value' => '5', 'currency_code' => 'USD'], $column, '["5","USD"]'],
            [['currency_code' => 'USD', 'value' => '5'], $column, '["5","USD"]'],
            [['value' => '5'], $column, '["5","USD"]'],
            [['value' => '5'], null, '["5"]'],
            [['value' => '5', 'currency_code' => 'USD', 'extra' => 'value'], $column, '["5","USD"]'],
            [(object) ['value' => '5', 'currency_code' => 'USD'], 'currency_money', '["5","USD"]'],
            ['["5","USD"]', null, '["5","USD"]'],
        ];
    }

    #[DataProvider('buildProvider')]
    public function testBuild(array|object|string $value, AbstractStructuredColumn|string|null $type, string $expected): void
    {
        $db = $this->getConnection();
        $qb = $db->getQueryBuilder();

        $params = [];
        $builder = new StructuredValueBuilder($qb);
        $expression = new StructuredValue($value, $type);

        $this->assertSame(':qp0', $builder->build($expression, $params));
        $this->assertEquals([':qp0' => new Param($expected, DataType::STRING)], $params);
    }

    public function testBuildNull(): void
    {
        $db = $this->getConnection();
        $qb = $db->getQueryBuilder();

        $params = [];
        $builder = new StructuredValueBuilder($qb);
        $expression = new StructuredValue(null);

        $this->assertSame('NULL', $builder->build($expression, $params));
        $this->assertSame([], $params);
    }

    public function testBuildQueryExpression(): void
    {
        $db = $this->getConnection();
        $qb = $db->getQueryBuilder();

        $params = [];
        $builder = new StructuredValueBuilder($qb);
        $expression = new StructuredValue((new Query($db))->select('json_field')->from('json_table'));

        $this->assertSame('(SELECT [json_field] FROM [json_table])', $builder->build($expression, $params));
        $this->assertSame([], $params);
    }
}
