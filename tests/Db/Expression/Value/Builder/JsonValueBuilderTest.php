<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Expression\Value\Builder;

use ArrayIterator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Expression\Value\Param;
use Yiisoft\Db\Constant\DataType;
use Yiisoft\Db\Expression\Value\JsonValue;
use Yiisoft\Db\Expression\Value\Builder\JsonValueBuilder;
use Yiisoft\Db\Schema\Data\LazyArray;
use Yiisoft\Db\Schema\Data\JsonLazyArray;
use Yiisoft\Db\Schema\Data\StructuredLazyArray;
use Yiisoft\Db\Tests\Support\JsonSerializableObject;
use Yiisoft\Db\Tests\Support\TestHelper;

/**
 * @group db
 */
final class JsonValueBuilderTest extends TestCase
{
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
            [new JsonValue(['a' => 1, 'b' => 2, 'd' => ['e' => 3]]), '{"a":1,"b":2,"d":{"e":3}}'],
            [['a' => 1, 'b' => null, 'c' => ['d' => 'e']], '{"a":1,"b":null,"c":{"d":"e"}}'],
            ['[1,2,3]', '[1,2,3]'],
            ['{"a":1,"b":null,"c":{"d":"e"}}', '{"a":1,"b":null,"c":{"d":"e"}}'],
        ];
    }

    #[DataProvider('buildProvider')]
    public function testBuild(mixed $value, string $expected): void
    {
        $db = TestHelper::createSqliteMemoryConnection();
        $qb = $db->getQueryBuilder();

        $params = [];
        $builder = new JsonValueBuilder($qb);
        $expression = new JsonValue($value);

        $this->assertSame(':qp0', $builder->build($expression, $params));
        $this->assertEquals([':qp0' => new Param($expected, DataType::STRING)], $params);
    }

    public function testBuildNull(): void
    {
        $db = TestHelper::createSqliteMemoryConnection();
        $qb = $db->getQueryBuilder();

        $params = [];
        $builder = new JsonValueBuilder($qb);
        $expression = new JsonValue(null);

        $this->assertSame('NULL', $builder->build($expression, $params));
        $this->assertSame([], $params);
    }

    public function testBuildQueryExpression(): void
    {
        $db = TestHelper::createSqliteMemoryConnection();
        $qb = $db->getQueryBuilder();

        $params = [];
        $builder = new JsonValueBuilder($qb);
        $expression = new JsonValue($db->select('json_field')->from('json_table'));

        $this->assertSame('(SELECT [json_field] FROM [json_table])', $builder->build($expression, $params));
        $this->assertSame([], $params);
    }
}
