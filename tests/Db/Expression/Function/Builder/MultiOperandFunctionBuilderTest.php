<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Expression\Function\Builder;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Command\Param;
use Yiisoft\Db\Constant\DataType;
use Yiisoft\Db\Expression\Function\Greatest;
use Yiisoft\Db\Expression\Function\Least;
use Yiisoft\Db\Expression\Function\Longest;
use Yiisoft\Db\Expression\Function\Shortest;
use Yiisoft\Db\Tests\Support\TestTrait;

class MultiOperandFunctionBuilderTest extends TestCase
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
        $queryString = '(SELECT [column] FROM [table] LIMIT 1)';

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
                [':qp0' => 1],
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
                'CASE GREATEST(LENGTH(expression), LENGTH(:pv0)) WHEN LENGTH(expression) THEN expression ELSE :qp1 END',
                [':pv0' => $stringParam, ':qp1' => $stringParam],
            ],
            'Longest with 3 operands' => [
                Longest::class,
                ['expression', $stringParam, $query],
                "CASE GREATEST(LENGTH(expression), LENGTH(:pv0), LENGTH($queryString)) WHEN LENGTH(expression) THEN :qp1 WHEN LENGTH(:pv2) THEN :qp3 ELSE $queryString END",
            ],

            'Shortest with 1 operand' => [
                Shortest::class,
                ['expression'],
                '(expression)',
            ],
            'Shortest with 2 operands' => [
                Shortest::class,
                ['expression', $stringParam],
                'CASE LEAST(LENGTH(expression), LENGTH(:pv0)) WHEN LENGTH(expression) THEN :qp1 ELSE :qp2 END',
            ],
            'Shortest with 3 operands' => [
                Shortest::class,
                ['expression', $stringParam, $query],
                '',
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
        $this->assertSame($expectedParams, $params);
    }
}
