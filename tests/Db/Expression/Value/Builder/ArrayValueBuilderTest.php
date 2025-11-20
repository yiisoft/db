<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Expression\Value\Builder;

use ArrayIterator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Expression\Value\Param;
use Yiisoft\Db\Constant\DataType;
use Yiisoft\Db\Expression\Value\ArrayValue;
use Yiisoft\Db\Expression\Value\Builder\ArrayValueBuilder;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Schema\Data\LazyArray;
use Yiisoft\Db\Schema\Data\LazyArrayInterface;
use Yiisoft\Db\Schema\Data\JsonLazyArray;
use Yiisoft\Db\Tests\Support\TestHelper;

/**
 * @group db
 */
final class ArrayValueBuilderTest extends TestCase
{
    public static function buildProvider(): array
    {
        return [
            [[1, 2, 3], '[1,2,3]'],
            [new ArrayIterator(['a', 'b', 'c']), '["a","b","c"]'],
            [new LazyArray('[1,2,3]'), '[1,2,3]'],
            [new JsonLazyArray('[1,2,3]'), '[1,2,3]'],
            [['a' => 1, 'b' => null], '{"a":1,"b":null}'],
            ['[1,2,3]', '[1,2,3]'],
            ['{"a":1,"b":null}', '{"a":1,"b":null}'],
        ];
    }

    #[DataProvider('buildProvider')]
    public function testBuild(iterable|LazyArrayInterface|string $value, string $expected): void
    {
        $db = TestHelper::createSqliteMemoryConnection();
        $qb = $db->getQueryBuilder();

        $params = [];
        $builder = new ArrayValueBuilder($qb);
        $expression = new ArrayValue($value);

        $this->assertSame(':qp0', $builder->build($expression, $params));
        $this->assertEquals([':qp0' => new Param($expected, DataType::STRING)], $params);
    }

    public function testBuildNull(): void
    {
        $db = TestHelper::createSqliteMemoryConnection();
        $qb = $db->getQueryBuilder();

        $params = [];
        $builder = new ArrayValueBuilder($qb);
        $expression = new ArrayValue(null);

        $this->assertSame('NULL', $builder->build($expression, $params));
        $this->assertSame([], $params);
    }

    public function testBuildQueryExpression(): void
    {
        $db = TestHelper::createSqliteMemoryConnection();
        $qb = $db->getQueryBuilder();

        $params = [];
        $builder = new ArrayValueBuilder($qb);
        $expression = new ArrayValue((new Query($db))->select('json_field')->from('json_table'));

        $this->assertSame('(SELECT [json_field] FROM [json_table])', $builder->build($expression, $params));
        $this->assertSame([], $params);
    }
}
