<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Expression;

use ArrayIterator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Command\Param;
use Yiisoft\Db\Constant\DataType;
use Yiisoft\Db\Expression\ArrayExpression;
use Yiisoft\Db\Expression\ArrayExpressionBuilder;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Schema\Data\LazyArray;
use Yiisoft\Db\Schema\Data\LazyArrayInterface;
use Yiisoft\Db\Schema\Data\JsonLazyArray;
use Yiisoft\Db\Tests\Support\TestTrait;

/**
 * @group db
 */
final class ArrayExpressionBuilderTest extends TestCase
{
    use TestTrait;

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
        $db = $this->getConnection();
        $qb = $db->getQueryBuilder();

        $params = [];
        $builder = new ArrayExpressionBuilder($qb);
        $expression = new ArrayExpression($value);

        $this->assertSame(':qp0', $builder->build($expression, $params));
        $this->assertEquals([':qp0' => new Param($expected, DataType::STRING)], $params);
    }

    public function testBuildNull(): void
    {
        $db = $this->getConnection();
        $qb = $db->getQueryBuilder();

        $params = [];
        $builder = new ArrayExpressionBuilder($qb);
        $expression = new ArrayExpression(null);

        $this->assertSame('NULL', $builder->build($expression, $params));
        $this->assertSame([], $params);
    }

    public function testBuildQueryExpression(): void
    {
        $db = $this->getConnection();
        $qb = $db->getQueryBuilder();

        $params = [];
        $builder = new ArrayExpressionBuilder($qb);
        $expression = new ArrayExpression($db->select('json_field')->from('json_table'));

        $this->assertSame('(SELECT [json_field] FROM [json_table])', $builder->build($expression, $params));
        $this->assertSame([], $params);
    }
}
