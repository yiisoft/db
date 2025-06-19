<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Command\Param;
use Yiisoft\Db\Constant\DataType;
use Yiisoft\Db\Expression\Function\Greatest;
use Yiisoft\Db\Expression\Function\Least;
use Yiisoft\Db\Expression\Function\Longest;
use Yiisoft\Db\Expression\Function\Shortest;
use Yiisoft\Db\Tests\Support\Assert;
use Yiisoft\Db\Tests\Support\DbHelper;
use Yiisoft\Db\Tests\Support\TestTrait;

abstract class AbstractMultiOperandFunctionBuilderTest extends TestCase
{
    use TestTrait;

    public static function dataClasses(): array
    {
        return [
            Greatest::class => [Greatest::class],
            Least::class => [Least::class],
            Longest::class => [Longest::class],
            Shortest::class => [Shortest::class],
        ];
    }

    public static function dataBuild(): array
    {
        $stringParam = new Param('string', DataType::STRING);
        $query = self::getDb()->select('column')->from('table')->limit(1);
        $queryString = DbHelper::replaceQuotes('(SELECT [[column]] FROM [[table]] LIMIT 1)', static::getDriverName());

        return [
            'Greatest with 1 operand' => [
                Greatest::class,
                ['1 + 2'],
                '(1 + 2)',
            ],
            'Greatest with 2 operands' => [
                Greatest::class,
                [1, '1 + 2'],
                'GREATEST(1, 1 + 2)',
            ],
            'Greatest with 4 operands' => [
                Greatest::class,
                [1, 1.5, '1 + 2', $query],
                "GREATEST(1, 1.5, 1 + 2, $queryString)",
            ],

            'Least with 1 operand' => [
                Least::class,
                ['1 + 2'],
                '(1 + 2)',
            ],
            'Least with 2 operands' => [
                Least::class,
                [1, '1 + 2'],
                'LEAST(1, 1 + 2)',
            ],
            'Least with 4 operands' => [
                Least::class,
                [1, 1.5, '1 + 2', $query],
                "LEAST(1, 1.5, 1 + 2, $queryString)",
            ],

            'Longest with 1 operand' => [
                Longest::class,
                ['expression'],
                '(expression)',
            ],
            'Longest with 2 operands' => [
                Longest::class,
                ['expression', $stringParam],
                DbHelper::replaceQuotes(
                    '(SELECT [[0]] FROM (SELECT expression [[0]] UNION SELECT :qp0 [[0]]) AS t ORDER BY LENGTH([[0]]) DESC LIMIT 1)',
                    static::getDriverName(),
                ),
                [':qp0' => $stringParam],
            ],
            'Longest with 3 operands' => [
                Longest::class,
                ['expression', $stringParam, $query],
                DbHelper::replaceQuotes(
                    "(SELECT [[0]] FROM (SELECT expression [[0]] UNION SELECT :qp0 [[0]] UNION SELECT $queryString [[0]]) AS t ORDER BY LENGTH([[0]]) DESC LIMIT 1)",
                    static::getDriverName(),
                ),
                [
                    ':qp0' => $stringParam,
                ],
            ],

            'Shortest with 1 operand' => [
                Shortest::class,
                ['expression'],
                '(expression)',
            ],
            'Shortest with 2 operands' => [
                Shortest::class,
                ['expression', $stringParam],
                DbHelper::replaceQuotes(
                    '(SELECT [[0]] FROM (SELECT expression [[0]] UNION SELECT :qp0 [[0]]) AS t ORDER BY LENGTH([[0]]) ASC LIMIT 1)',
                    static::getDriverName(),
                ),
                [':qp0' => $stringParam],
            ],
            'Shortest with 3 operands' => [
                Shortest::class,
                ['expression', $stringParam, $query],
                DbHelper::replaceQuotes(
                    "(SELECT [[0]] FROM (SELECT expression [[0]] UNION SELECT :qp0 [[0]] UNION SELECT $queryString [[0]]) AS t ORDER BY LENGTH([[0]]) ASC LIMIT 1)",
                    static::getDriverName(),
                ),
                [
                    ':qp0' => $stringParam,
                ],
            ],
        ];
    }

    #[DataProvider('dataBuild')]
    public function testBuild(string $class, array $operands, string $expected, array $expectedParams = []): void
    {
        $db = $this->getConnection();
        $qb = $db->getQueryBuilder();

        $expression = new $class(...$operands);
        $params = [];

        $this->assertSame($expected, $qb->buildExpression($expression, $params));
        Assert::arraysEquals($expectedParams, $params);
    }

    #[DataProvider('dataClasses')]
    public function testBuildWithoutOperands(string $class): void
    {
        $db = $this->getConnection();
        $qb = $db->getQueryBuilder();

        $expression = new $class();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("The $class expression must have at least one operand.");

        $qb->buildExpression($expression);
    }
}
