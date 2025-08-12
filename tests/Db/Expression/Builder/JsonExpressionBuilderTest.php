<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Expression\Builder;

use ArrayIterator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Expression\Param;
use Yiisoft\Db\Constant\DataType;
use Yiisoft\Db\Expression\JsonExpression;
use Yiisoft\Db\Expression\Builder\JsonExpressionBuilder;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Schema\Data\LazyArray;
use Yiisoft\Db\Schema\Data\JsonLazyArray;
use Yiisoft\Db\Schema\Data\StructuredLazyArray;
use Yiisoft\Db\Tests\Support\JsonSerializableObject;
use Yiisoft\Db\Tests\Support\TestTrait;

/**
 * @group db
 */
final class JsonExpressionBuilderTest extends TestCase
{
    use TestTrait;

    public static function buildProvider(): array
    {
        return [
            ['', '""'],
            [1, '1'],
            [true, 'true'],
            [false, 'false'],
            [[null], '[null]'],
            [['nil' => null], '{"nil":null}'],
            [[1, 2, 3], '[1,2,3]'],
            [new ArrayIterator(['a', 'b', 'c']), '["a","b","c"]'],
            [new ArrayIterator(['a' => 1, 'b' => 2]), '{"a":1,"b":2}'],
            [new JsonSerializableObject(['a' => 1, 'b' => 2]), '{"a":1,"b":2}'],
            [new LazyArray('[1,2,3]'), '[1,2,3]'],
            [new JsonLazyArray('[1,2,3]'), '[1,2,3]'],
            [new StructuredLazyArray('["5","USD"]'), '["5","USD"]'],
            [new JsonExpression(['a' => 1, 'b' => 2, 'd' => ['e' => 3]]), '{"a":1,"b":2,"d":{"e":3}}'],
            [['a' => 1, 'b' => null, 'c' => ['d' => 'e']], '{"a":1,"b":null,"c":{"d":"e"}}'],
            ['[1,2,3]', '[1,2,3]'],
            ['{"a":1,"b":null,"c":{"d":"e"}}', '{"a":1,"b":null,"c":{"d":"e"}}'],
        ];
    }

    #[DataProvider('buildProvider')]
    public function testBuild(mixed $value, string $expected): void
    {
        $db = $this->getConnection();
        $qb = $db->getQueryBuilder();

        $params = [];
        $builder = new JsonExpressionBuilder($qb);
        $expression = new JsonExpression($value);

        $this->assertSame(':qp0', $builder->build($expression, $params));
        $this->assertEquals([':qp0' => new Param($expected, DataType::STRING)], $params);
    }

    public function testBuildNull(): void
    {
        $db = $this->getConnection();
        $qb = $db->getQueryBuilder();

        $params = [];
        $builder = new JsonExpressionBuilder($qb);
        $expression = new JsonExpression(null);

        $this->assertSame('NULL', $builder->build($expression, $params));
        $this->assertSame([], $params);
    }

    public function testBuildQueryExpression(): void
    {
        $db = $this->getConnection();
        $qb = $db->getQueryBuilder();

        $params = [];
        $builder = new JsonExpressionBuilder($qb);
        $expression = new JsonExpression((new Query($db))->select('json_field')->from('json_table'));

        $this->assertSame('(SELECT [json_field] FROM [json_table])', $builder->build($expression, $params));
        $this->assertSame([], $params);
    }
}
