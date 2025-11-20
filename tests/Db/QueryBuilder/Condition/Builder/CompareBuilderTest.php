<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\QueryBuilder\Condition\Builder;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Expression\Value\Param;
use Yiisoft\Db\Constant\DataType;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\QueryBuilder\Condition\Builder\CompareBuilder;
use Yiisoft\Db\QueryBuilder\Condition\Equals;
use Yiisoft\Db\QueryBuilder\Condition\GreaterThan;
use Yiisoft\Db\QueryBuilder\Condition\GreaterThanOrEqual;
use Yiisoft\Db\QueryBuilder\Condition\LessThan;
use Yiisoft\Db\QueryBuilder\Condition\LessThanOrEqual;
use Yiisoft\Db\QueryBuilder\Condition\NotEquals;
use Yiisoft\Db\Tests\Support\TestHelper;

use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertSame;

/**
 * @group db
 */
final class CompareBuilderTest extends TestCase
{
    public static function dataBuildWithStringColumn(): iterable
    {
        yield 'equals' => [Equals::class, '='];
        yield 'not equals' => [NotEquals::class, '<>'];
        yield 'greater than' => [GreaterThan::class, '>'];
        yield 'greater than or equal' => [GreaterThanOrEqual::class, '>='];
        yield 'less than' => [LessThan::class, '<'];
        yield 'less than or equal' => [LessThanOrEqual::class, '<='];
    }

    #[DataProvider('dataBuildWithStringColumn')]
    public function testBuildWithStringColumn(string $conditionClass, string $operator): void
    {
        $qb = TestHelper::createSqliteMemoryConnection()->getQueryBuilder();
        $condition = new $conditionClass('id', 42);
        $params = [];

        $result = (new CompareBuilder($qb))->build($condition, $params);

        assertSame("[id] $operator 42", $result);
        assertSame([], $params);
    }

    public static function dataBuildWithExpressionColumn(): iterable
    {
        yield 'equals' => [Equals::class, '='];
        yield 'not equals' => [NotEquals::class, '<>'];
        yield 'greater than' => [GreaterThan::class, '>'];
        yield 'greater than or equal' => [GreaterThanOrEqual::class, '>='];
        yield 'less than' => [LessThan::class, '<'];
        yield 'less than or equal' => [LessThanOrEqual::class, '<='];
    }

    #[DataProvider('dataBuildWithExpressionColumn')]
    public function testBuildWithExpressionColumn(string $conditionClass, string $operator): void
    {
        $qb = TestHelper::createSqliteMemoryConnection()->getQueryBuilder();
        $columnExpression = new Expression('UPPER(name)');
        $condition = new $conditionClass($columnExpression, 'JOHN');
        $params = [];

        $result = (new CompareBuilder($qb))->build($condition, $params);

        assertSame("UPPER(name) $operator :qp0", $result);
        assertEquals([':qp0' => new Param('JOHN', DataType::STRING)], $params);
    }

    public static function dataBuildWithFunctionColumn(): iterable
    {
        yield 'equals' => [Equals::class, '='];
        yield 'not equals' => [NotEquals::class, '<>'];
        yield 'greater than' => [GreaterThan::class, '>'];
        yield 'greater than or equal' => [GreaterThanOrEqual::class, '>='];
        yield 'less than' => [LessThan::class, '<'];
        yield 'less than or equal' => [LessThanOrEqual::class, '<='];
    }

    #[DataProvider('dataBuildWithFunctionColumn')]
    public function testBuildWithFunctionColumn(string $conditionClass, string $operator): void
    {
        $qb = TestHelper::createSqliteMemoryConnection()->getQueryBuilder();
        $condition = new $conditionClass('COUNT(*)', 5);
        $params = [];

        $result = (new CompareBuilder($qb))->build($condition, $params);

        assertSame("COUNT(*) $operator 5", $result);
        assertSame([], $params);
    }

    public static function dataBuildWithNullValue(): iterable
    {
        yield 'equals' => [Equals::class, '[status] IS NULL'];
        yield 'not equals' => [NotEquals::class, '[status] IS NOT NULL'];
        yield 'greater than' => [GreaterThan::class, '[status] > NULL'];
        yield 'greater than or equal' => [GreaterThanOrEqual::class, '[status] >= NULL'];
        yield 'less than' => [LessThan::class, '[status] < NULL'];
        yield 'less than or equal' => [LessThanOrEqual::class, '[status] <= NULL'];
    }

    #[DataProvider('dataBuildWithNullValue')]
    public function testBuildWithNullValue(string $conditionClass, string $expectedResult): void
    {
        $qb = TestHelper::createSqliteMemoryConnection()->getQueryBuilder();
        $condition = new $conditionClass('status', null);
        $params = [];

        $result = (new CompareBuilder($qb))->build($condition, $params);

        assertSame($expectedResult, $result);
        assertSame([], $params);
    }

    public static function dataBuildWithExpressionValue(): iterable
    {
        yield 'equals' => [Equals::class, '='];
        yield 'not equals' => [NotEquals::class, '<>'];
        yield 'greater than' => [GreaterThan::class, '>'];
        yield 'greater than or equal' => [GreaterThanOrEqual::class, '>='];
        yield 'less than' => [LessThan::class, '<'];
        yield 'less than or equal' => [LessThanOrEqual::class, '<='];
    }

    #[DataProvider('dataBuildWithExpressionValue')]
    public function testBuildWithExpressionValue(string $conditionClass, string $operator): void
    {
        $qb = TestHelper::createSqliteMemoryConnection()->getQueryBuilder();
        $valueExpression = new Expression('NOW()');
        $condition = new $conditionClass('created_at', $valueExpression);
        $params = [];

        $result = (new CompareBuilder($qb))->build($condition, $params);

        assertSame("[created_at] $operator NOW()", $result);
        assertSame([], $params);
    }

    public static function dataBuildWithDifferentColumnTypes(): iterable
    {
        $operators = [
            'equals' => [Equals::class, '='],
            'not equals' => [NotEquals::class, '<>'],
            'greater than' => [GreaterThan::class, '>'],
            'greater than or equal' => [GreaterThanOrEqual::class, '>='],
            'less than' => [LessThan::class, '<'],
            'less than or equal' => [LessThanOrEqual::class, '<='],
        ];

        $columnTypes = [
            'simple column' => ['name', '[name]'],
            'column with table prefix' => ['user.name', '[user].[name]'],
            'column with function' => ['COUNT(*)', 'COUNT(*)'],
            'column with expression' => ['UPPER(title)', 'UPPER(title)'],
            'column with subquery' => ['(SELECT id FROM users)', '(SELECT id FROM users)'],
        ];

        foreach ($columnTypes as $columnName => $columnData) {
            foreach ($operators as $operatorName => $operatorData) {
                yield "$columnName with $operatorName" => [
                    $operatorData[0], // condition class
                    $operatorData[1], // operator
                    $columnData[0],   // column
                    $columnData[1],   // expected column
                ];
            }
        }
    }

    #[DataProvider('dataBuildWithDifferentColumnTypes')]
    public function testBuildWithDifferentColumnTypes(
        string $conditionClass,
        string $operator,
        string $column,
        string $expectedColumn,
    ): void {
        $qb = TestHelper::createSqliteMemoryConnection()->getQueryBuilder();
        $condition = new $conditionClass($column, 5);
        $params = [];

        $result = (new CompareBuilder($qb))->build($condition, $params);

        assertSame("$expectedColumn $operator 5", $result);
        assertSame([], $params);
    }

    public static function dataBuildWithDifferentValueTypes(): iterable
    {
        $operators = [
            'equals' => [Equals::class, '='],
            'not equals' => [NotEquals::class, '<>'],
            'greater than' => [GreaterThan::class, '>'],
            'greater than or equal' => [GreaterThanOrEqual::class, '>='],
            'less than' => [LessThan::class, '<'],
            'less than or equal' => [LessThanOrEqual::class, '<='],
        ];

        $valueTypes = [
            'string value' => ['hello', ':qp0', [':qp0' => new Param('hello', DataType::STRING)]],
            'integer value' => [42, '42', []],
            'float value' => [3.14, '3.14', []],
            'boolean true' => [true, 'TRUE', []],
            'boolean false' => [false, 'FALSE', []],
        ];

        foreach ($valueTypes as $valueName => $valueData) {
            foreach ($operators as $operatorName => $operatorData) {
                yield "$valueName with $operatorName" => [
                    $operatorData[0], // condition class
                    $operatorData[1], // operator
                    $valueData[0],    // value
                    $valueData[1],    // expected value
                    $valueData[2],    // expected params
                ];
            }
        }
    }

    #[DataProvider('dataBuildWithDifferentValueTypes')]
    public function testBuildWithDifferentValueTypes(
        string $conditionClass,
        string $operator,
        mixed $value,
        ?string $expectedValue,
        array $expectedParams,
    ): void {
        $qb = TestHelper::createSqliteMemoryConnection()->getQueryBuilder();
        $condition = new $conditionClass('column', $value);
        $params = [];

        $result = (new CompareBuilder($qb))->build($condition, $params);

        assertSame("[column] $operator $expectedValue", $result);
        assertEquals($expectedParams, $params);
    }

    public static function dataBuildWithComplexExpression(): iterable
    {
        yield 'equals' => [Equals::class, '='];
        yield 'not equals' => [NotEquals::class, '<>'];
        yield 'greater than' => [GreaterThan::class, '>'];
        yield 'greater than or equal' => [GreaterThanOrEqual::class, '>='];
        yield 'less than' => [LessThan::class, '<'];
        yield 'less than or equal' => [LessThanOrEqual::class, '<='];
    }

    #[DataProvider('dataBuildWithComplexExpression')]
    public function testBuildWithComplexExpression(string $conditionClass, string $operator): void
    {
        $qb = TestHelper::createSqliteMemoryConnection()->getQueryBuilder();
        $columnExpression = new Expression('COALESCE(name, :default)', [':default' => 'Unknown']);
        $valueExpression = new Expression('UPPER(:value)', [':value' => 'john']);
        $condition = new $conditionClass($columnExpression, $valueExpression);
        $params = [];

        $result = (new CompareBuilder($qb))->build($condition, $params);

        assertSame("COALESCE(name, :default) $operator UPPER(:value)", $result);
        assertSame([':default' => 'Unknown', ':value' => 'john'], $params);
    }
}
