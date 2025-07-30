<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\QueryBuilder\Condition\Builder;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Command\Param;
use Yiisoft\Db\Constant\DataType;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\QueryBuilder\Condition\Builder\EqualsBuilder;
use Yiisoft\Db\QueryBuilder\Condition\Equals;
use Yiisoft\Db\Tests\Support\TestTrait;

use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertSame;

/**
 * @group db
 */
final class EqualsBuilderTest extends TestCase
{
    use TestTrait;

    public function testBuildWithStringColumn(): void
    {
        $qb = $this->getConnection()->getQueryBuilder();
        $equalsCondition = new Equals('id', 42);
        $params = [];

        $result = (new EqualsBuilder($qb))->build($equalsCondition, $params);

        assertSame('[id] = 42', $result);
        assertSame([], $params);
    }

    public function testBuildWithExpressionColumn(): void
    {
        $qb = $this->getConnection()->getQueryBuilder();
        $columnExpression = new Expression('UPPER(name)');
        $equalsCondition = new Equals($columnExpression, 'JOHN');
        $params = [];

        $result = (new EqualsBuilder($qb))->build($equalsCondition, $params);

        assertSame('UPPER(name) = :qp0', $result);
        assertEquals([':qp0' => new Param('JOHN', DataType::STRING)], $params);
    }

    public function testBuildWithFunctionColumn(): void
    {
        $qb = $this->getConnection()->getQueryBuilder();
        $equalsCondition = new Equals('COUNT(*)', 5);
        $params = [];

        $result = (new EqualsBuilder($qb))->build($equalsCondition, $params);

        assertSame('COUNT(*) = 5', $result);
        assertSame([], $params);
    }

    public function testBuildWithNullValue(): void
    {
        $qb = $this->getConnection()->getQueryBuilder();
        $equalsCondition = new Equals('status', null);
        $params = [];

        $result = (new EqualsBuilder($qb))->build($equalsCondition, $params);

        assertSame('[status] IS NULL', $result);
        assertSame([], $params);
    }

    public function testBuildWithExpressionValue(): void
    {
        $qb = $this->getConnection()->getQueryBuilder();
        $valueExpression = new Expression('NOW()');
        $equalsCondition = new Equals('created_at', $valueExpression);
        $params = [];

        $result = (new EqualsBuilder($qb))->build($equalsCondition, $params);

        assertSame('[created_at] = NOW()', $result);
        assertSame([], $params);
    }

    public static function dataColumnTypes(): iterable
    {
        yield 'simple column' => ['name', '[name]'];
        yield 'column with table prefix' => ['user.name', '[user].[name]'];
        yield 'column with function' => ['COUNT(*)', 'COUNT(*)'];
        yield 'column with expression' => ['UPPER(title)', 'UPPER(title)'];
        yield 'column with subquery' => ['(SELECT id FROM users)', '(SELECT id FROM users)'];
    }

    #[DataProvider('dataColumnTypes')]
    public function testBuildWithDifferentColumnTypes(string $column, string $expectedColumn): void
    {
        $qb = $this->getConnection()->getQueryBuilder();
        $equalsCondition = new Equals($column, 5);
        $params = [];

        $result = (new EqualsBuilder($qb))->build($equalsCondition, $params);

        assertSame($expectedColumn . ' = 5', $result);
        assertSame([], $params);
    }

    public static function dataValueTypes(): iterable
    {
        yield 'string value' => ['hello', ':qp0', [':qp0' => new Param('hello', DataType::STRING)]];
        yield 'integer value' => [42, '42', []];
        yield 'float value' => [3.14, '3.14', []];
        yield 'boolean true' => [true, 'TRUE', []];
        yield 'boolean false' => [false, 'FALSE', []];
        yield 'null value' => [null, null, []];
    }

    #[DataProvider('dataValueTypes')]
    public function testBuildWithDifferentValueTypes(mixed $value, ?string $expectedValue, array $expectedParams): void
    {
        $qb = $this->getConnection()->getQueryBuilder();
        $equalsCondition = new Equals('column', $value);
        $params = [];

        $result = (new EqualsBuilder($qb))->build($equalsCondition, $params);

        ($value === null)
            ? assertSame('[column] IS NULL', $result)
            : assertSame('[column] = ' . $expectedValue, $result);
        assertEquals($expectedParams, $params);
    }

    public function testBuildWithComplexExpression(): void
    {
        $qb = $this->getConnection()->getQueryBuilder();
        $columnExpression = new Expression('COALESCE(name, :default)', [':default' => 'Unknown']);
        $valueExpression = new Expression('UPPER(:value)', [':value' => 'john']);
        $equalsCondition = new Equals($columnExpression, $valueExpression);
        $params = [];

        $result = (new EqualsBuilder($qb))->build($equalsCondition, $params);

        assertSame('COALESCE(name, :default) = UPPER(:value)', $result);
        assertSame([':default' => 'Unknown', ':value' => 'john'], $params);
    }
}
